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
          // Check if content type is JSON
          var isJson = settings.contentType &&
                       settings.contentType.indexOf('application/json') !== -1;

          if (isJson && settings.data && typeof settings.data === 'string') {
            // Parse JSON, add token, re-stringify
            try {
              var jsonData = JSON.parse(settings.data);
              jsonData.csrf_token = token;
              settings.data = JSON.stringify(jsonData);
            } catch (e) {
              // If JSON parse fails, add as header instead
              xhr.setRequestHeader('X-CSRF-Token', token);
            }
          } else if (settings.data && typeof settings.data === 'string') {
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
