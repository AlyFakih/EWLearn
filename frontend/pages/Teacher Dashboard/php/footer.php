<?php
// Page fragment: only ever included from php/header.php (or php/footer.php),
// after the instructor guard has already run. Refuse direct HTTP access - on
// its own this file has no $db_handle/$user_id and would emit PHP warnings and
// a stack trace containing absolute server paths to an anonymous caller.
if (!function_exists("auth_user_id") || !auth_user_id()) {
    http_response_code(403);
    exit;
}
?>
      <!-- End of main content -->
      </main>
    </div>
  </div>

  <!-- jQuery is already loaded by header_includes.php in <head> -->
  <script src="./js/sidebar-toggle.js"></script>
  <?php if (isset($page_js) && !empty($page_js)): ?>
  <script src="./js/<?php echo htmlspecialchars($page_js); ?>.js"></script>
  <?php endif; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize notification system
      const notificationSystem = new NotificationSystem();
      notificationSystem.init();

      // Initialize academic calendar if element exists
      const calendarEl = document.getElementById('calendar');
      if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          height: 450,
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
          },
          events: '../common/calendar_api.php?action=get_events',
          eventClick: function(info) {
            const event = info.event;
            alert(`Event: ${event.title}\nTime: ${event.start.toLocaleString()}\n${event.extendedProps.description || ''}`);
          },
          eventDidMount: function(info) {
            // Native browser tooltip - Bootstrap's JS (which $(...).tooltip()
            // requires) is never actually loaded on this page, so calling it
            // threw "$(...).tooltip is not a function" on every calendar render.
            info.el.setAttribute('title', info.event.extendedProps.description || info.event.title);
          }
        });
        calendar.render();
      }
    });
  </script>
</body>
</html>
