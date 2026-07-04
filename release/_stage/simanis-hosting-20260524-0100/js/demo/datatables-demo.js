// Call the dataTables jQuery plugin with error handling
$(document).ready(function() {
  console.log('DataTables demo: jQuery ready');
  console.log('DataTables demo: jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'not loaded');
  console.log('DataTables demo: DataTable available?', typeof $.fn.DataTable !== 'undefined');
  
  if (typeof $.fn.DataTable !== 'undefined') {
    // Initialize all tables with dataTable class (except tblWaliKelas which has its own handler)
    if ($('#dataTable').length && !$.fn.dataTable.isDataTable('#dataTable')) {
      console.log('DataTables demo: Initializing #dataTable');
      $('#dataTable').DataTable();
    }
    
    // Skip tblWaliKelas - it has its own initialization in walikelas.php
    // This prevents double initialization
    console.log('DataTables demo: Skipping #tblWaliKelas (handled separately)');
  } else {
    console.error('DataTables demo: DataTable plugin not available');
  }
});
