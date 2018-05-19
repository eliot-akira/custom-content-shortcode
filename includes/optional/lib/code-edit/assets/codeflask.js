function CodeFlask() {

}

CodeFlask.prototype.run = function(selector, opts) {
    var target = document.querySelectorAll(selector);

    if(target.length > 1) {
        throw 'CodeFlask.js ERROR: run() expects only one element, ' +
        target.length + ' given. Use .runAll() instead.';
    } else {
        this.scaffold(target[0], false, opts);
    }
}

CodeFlask.prototype.runAll = function(selector, opts) {
    // Remove update API for bulk rendering
    this.update = null;
    this.onUpdate = null;

    var target = document.querySelectorAll(selector);

    var i;
    for(i=0; i < target.length; i++) {
        this.scaffold(target[i], true, opts);
    }
}

CodeFlask.prototype.scaffold = function(target, isMultiple, opts) {
    var textarea = document.createElement('TEXTAREA'),
        highlightPre = document.createElement('PRE'),
        highlightCode = document.createElement('CODE'),
        initialCode = target.textContent,
        lang;

    opts.language = this.handleLanguage(opts.language);

    this.defaultLanguage = target.dataset.language || opts.language || 'markup';


    // Prevent these vars from being refreshed when rendering multiple
    // instances
    if(!isMultiple) {
        this.textarea = textarea;
        this.highlightCode = highlightCode;
    }

    this.inputListeners = []

    target.classList.add('CodeFlask');
    textarea.classList.add('CodeFlask__textarea');
    highlightPre.classList.add('CodeFlask__pre');
    highlightCode.classList.add('CodeFlask__code');
    highlightCode.classList.add('language-' + this.defaultLanguage);

    // Fixing iOS "drunk-text" issue
    if(/iPad|iPhone|iPod/.test(navigator.platform)) {
        highlightCode.style.paddingLeft = '3px';
    }

    // Appending editor elements to DOM
    target.innerHTML = '';
    target.appendChild(textarea);
    target.appendChild(highlightPre);
    highlightPre.appendChild(highlightCode);

    // Render initial code inside tag
    textarea.value = initialCode;
    this.renderOutput(highlightCode, textarea);
    Prism.highlightAll();

    this.handleInput(textarea, highlightCode, highlightPre);
    this.handleScroll(textarea, highlightPre);
}

CodeFlask.prototype.renderOutput = function(highlightCode, input) {
    var val = highlightCode.innerHTML = input.value
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;") + "\n";

    this.triggerInputListeners(input.value)
}

CodeFlask.prototype.triggerInputListeners = function(value, isPaste) {
  this.inputListeners.map(function(cb) {
    cb(value, isPaste)
  })
}

CodeFlask.prototype.handleInput = function(textarea, highlightCode, highlightPre) {

    var self = this,
        input,
        selStartPos,
        inputVal,
        roundedScroll,
        lastKey,
        skip;

    textarea.addEventListener('input', function(e) {
        input = this;
        self.renderOutput(highlightCode, input);
        Prism.highlightAll();
    })

    // Get tab complete defitions

    var shortcuts = window.CodeFlaskShortcuts || {
      shortPair: ['php'],
      shortPairWithAttr: ['loop','if','is','for','related','users'],
      shortSingle: ['field','taxonomy','each','user'],
      tagPair: ['h1','h2','h3','h4','b','ul','ol','li','script','style'],
      tagPairWithAttr: ['div','a'],
      tagSingle: ['br','hr']
    }

    textarea.addEventListener('keydown', function(e) {

        input = this,
        selStartPos = input.selectionStart,
        selEndPos = input.selectionEnd,
        inputVal = input.value;

        skip = false

//console.log(e.keyCode)

        // If TAB pressed
        // auto-complete or insert two spaces
        if (e.keyCode === 9){

          var tag = self.getLastTag(inputVal, selStartPos)
          var insertVal
          var tagLen = tag.length // Replace tag
          var posDiff = 2

          if (!tagLen) {
            // Soft tab: 2 spaces
            insertVal = '  '
            tagLen = 0 // Don't replace tag
          } else if (shortcuts.shortPair.indexOf(tag)>=0) {
            // Shortcode pair
            insertVal = '['+tag+'][/'+tag+']'
          } else if (shortcuts.shortPairWithAttr.indexOf(tag)>=0) {
            // Shortcode pair with attributes
            insertVal = '['+tag+' ][/'+tag+']'
          } else if (shortcuts.shortSingle.indexOf(tag)>=0) {
            // Shortcode single
            insertVal = '['+tag+' ]'
          } else if (shortcuts.tagPair.indexOf(tag)>=0) {
            // Tag pair
            insertVal = '<'+tag+'></'+tag+'>'
          } else  if (shortcuts.tagPairWithAttr.indexOf(tag)>=0) {
            // Tag pair with attributes
            insertVal = '<'+tag+' ></'+tag+'>'
          } else if (shortcuts.tagSingle.indexOf(tag)>=0) {
            // Tag single
            insertVal = '<'+tag+'>'
          } else {
            // Soft tab: 2 spaces
            insertVal = '  '
            tagLen = 0 // Don't replace tag
          }

          input.value = inputVal.substring(0, selStartPos - tagLen)
            + insertVal + inputVal.substring(selEndPos, input.value.length);

          input.selectionStart = selStartPos + posDiff;
          input.selectionEnd = selStartPos + posDiff;

        // ENTER auto-indent
        } else if (e.keyCode === 13){

          // Count current line indent
          var indent = self.getIndent(inputVal, selStartPos)
//console.log('INDENT', indent)

          input.value = inputVal.substring(0, selStartPos)
            + "\n" + (" ".repeat(indent)) + inputVal.substring(selEndPos, input.value.length);
          input.selectionStart = selStartPos + (indent+1);
          input.selectionEnd = selStartPos + (indent+1);

        // Opening square bracket
/*        } else if (e.keyCode === 219){

          input.value = inputVal.substring(0, selStartPos)
            + "[]" + inputVal.substring(selStartPos, input.value.length);
          input.selectionStart = selStartPos + 1;
          input.selectionEnd = selStartPos + 1;
*/

        } else skip = true

        lastKey = e.keyCode

        if (skip) return

        e.preventDefault();
        self.renderOutput(highlightCode, input);
        Prism.highlightAll();
    });
}

CodeFlask.prototype.getIndent = function(val, start) {

  var last = val.length - 1
  var found = false

  // Find end of last line
  for (var i = start - 1; i >= 0; i--) {
//console.log('back', val[i])
    if (val[i]=="\n") {
      found = true
      break
    }
  }
  if (!found) return 0

  var indent = 0
  i++
  // Count spaces from beginning of this line
  while (i<=start) {
//console.log('forward', val[i])
    if (val[i]==" ") {
      indent++
      i++
    } else break
  }
  return indent
}

CodeFlask.prototype.getLastTag = function(val, start) {

  var last = val.length - 1
  var found = false
  var ends = [' ',"\n",']','>']

  // Find last space or new line
  for (var i = start - 1; i >= 0; i--) {
//console.log('back', val[i])
    if (ends.indexOf(val[i])>=0 || i===0) {
      found = true
      if (i!==0) i++ // Skip delimiter
      break
    }
  }
  if (!found) return ''

  // Find tag
  var tag = ''
  for (; i < start; i++) {
    tag += val[i]
  }
  return tag
}

CodeFlask.prototype.handleScroll = function(textarea, highlightPre) {
/*
    textarea.addEventListener('scroll', function(){

        roundedScroll = Math.floor(this.scrollTop);

        // Fixes issue of desync text on mouse wheel, fuck Firefox.
        if(navigator.userAgent.toLowerCase().indexOf('firefox') < 0) {
            this.scrollTop = roundedScroll;
        }

        highlightPre.style.top = "-" + roundedScroll + "px";
    });
*/
}

CodeFlask.prototype.handleLanguage = function(lang) {
    if(lang.match(/html|xml|xhtml|svg/)) {
        return 'markup';
    } else  if(lang.match(/js/)) {
        return 'javascript';
    } else {
        return lang;
    }
}

CodeFlask.prototype.onUpdate = function(cb) {
    if(typeof(cb) == "function") {
      this.inputListeners.push(cb)
/*        this.textarea.addEventListener('input', function(e) {
            cb(this.value);
        });
*/
    }else{
        throw 'CodeFlask.js ERROR: onUpdate() expects function, ' +
        typeof(cb) + ' given instead.';
    }
}

CodeFlask.prototype.update = function(string) {
    var evt = document.createEvent("HTMLEvents");

    this.textarea.value = string;
    this.renderOutput(this.highlightCode, this.textarea);
    Prism.highlightAll();

    evt.initEvent("input", false, true);
    this.textarea.dispatchEvent(evt);
}
