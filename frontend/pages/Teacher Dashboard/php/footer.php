  <!-- End of main content -->
  </section>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <?php if (isset($page_js) && !empty($page_js)): ?>
  <script src="./js/<?php echo $page_js; ?>.js"></script>
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
          events: '../../common/calendar_api.php?action=get_events',
          eventClick: function(info) {
            const event = info.event;
            alert(`Event: ${event.title}\nTime: ${event.start.toLocaleString()}\n${event.extendedProps.description || ''}`);
          },
          eventDidMount: function(info) {
            // Add tooltips to events
            $(info.el).tooltip({
              title: info.event.extendedProps.description || info.event.title,
              placement: 'top',
              trigger: 'hover',
              container: 'body'
            });
          }
        });
        calendar.render();
      }
    });
  </script>
</body>
</html>
