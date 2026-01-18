/**
 * Minimal Modernizr shim for Foundation 5 compatibility
 *
 * This replaces the full Modernizr library with just the feature detections
 * that Foundation's JavaScript actually uses. Modern browsers support all
 * these features natively, so we just provide the expected interface.
 */
window.Modernizr = {
  // Touch detection - used by Foundation tabs, topbar, dropdown, tooltip, clearing
  touch: ('ontouchstart' in window) || (navigator.maxTouchPoints > 0),

  // CSS transitions - used by Foundation alert
  csstransitions: (function() {
    var style = document.createElement('div').style;
    return 'transition' in style ||
           'WebkitTransition' in style ||
           'MozTransition' in style ||
           'OTransition' in style;
  })()
};
