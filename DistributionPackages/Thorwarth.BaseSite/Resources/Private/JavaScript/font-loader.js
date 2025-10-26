// Web Font Loader for Adobe TypeKit fonts
// This loads fonts asynchronously to prevent render blocking

if (typeof WebFontConfig === 'undefined') {
  window.WebFontConfig = {
    typekit: { 
      id: 'lug5qgq'
    },
    timeout: 2000,
    active: function() {
      // Fonts have loaded successfully
      sessionStorage.fontsLoaded = true;
    },
    classes: false // Don't add loading classes to reduce repaints
  };
}

// Load Web Font Loader script asynchronously
(function(d) {
  var wf = d.createElement('script'), s = d.scripts[0];
  wf.src = 'https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js';
  wf.async = true;
  s.parentNode.insertBefore(wf, s);
})(document);

// If fonts were loaded in a previous session, add class immediately
if (sessionStorage.fontsLoaded) {
  document.documentElement.classList.add('wf-active');
}