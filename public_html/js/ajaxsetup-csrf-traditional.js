/**
 * jQuery $.ajaxSetup Configuration
 *
 * Configures jQuery AJAX defaults:
 * 1. traditional: true - PHP-compatible array serialization
 * 2. CSRF token injection - Automatically adds csrf_token to POST/PUT/DELETE/PATCH requests
 *
 * This script is idempotent - safe to include multiple times.
 * Requires: jQuery loaded first, and a <meta name="csrf-token" content="..."> tag in the page.
 */
(function($) {
  // Guard against multiple initializations
  if ($.ajaxSetupCsrfTraditionalInitialized) {
    return;
  }
  $.ajaxSetupCsrfTraditionalInitialized = true;

  $.ajaxSetup({
    traditional: true,
    beforeSend: function(xhr, settings) {
      // Only add CSRF token for state-changing requests
      if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type)) {
        var token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
          if (settings.data && typeof settings.data === 'string') {
            settings.data += '&csrf_token=' + encodeURIComponent(token);
          } else if (settings.data && typeof settings.data === 'object') {
            settings.data.csrf_token = token;
          } else {
            settings.data = 'csrf_token=' + encodeURIComponent(token);
          }
        }
      }
    }
  });
})(jQuery);
