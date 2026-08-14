// Shared mobile sidebar toggle for the Teacher Dashboard shell.
// Works with or without jQuery being loaded yet, since some pages load
// jQuery after this script.
document.addEventListener('DOMContentLoaded', function () {
  var sidebar = document.querySelector('.sidebar');
  var backdrop = document.querySelector('.sidebar-backdrop');
  var toggleBtn = document.querySelector('.hamburger');

  if (!sidebar || !toggleBtn) return;

  function openSidebar() {
    sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('open');
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
  }

  toggleBtn.addEventListener('click', function () {
    if (sidebar.classList.contains('open')) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });

  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  // Close on navigation link click (mobile) so the drawer doesn't stay open
  // after the new page loads a fresh copy of this script.
  sidebar.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', closeSidebar);
  });

  // Close on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });
});
