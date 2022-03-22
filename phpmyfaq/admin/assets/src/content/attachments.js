// needs to be refactored

document.addEventListener('DOMContentLoaded', () => {
  const attachmentTable = document.getElementById('attachment-table');

  attachmentTable.addEventListener('click', (event) => {
    event.preventDefault();
    const isButton = event.target.nodeName === 'BUTTON';

    if (isButton) {
      const attachmentId = event.target.getAttribute('data-attachment-id');
      const csrf = event.target.getAttribute('data-csrf');

      $('#pmf-admin-saving-data-indicator').html(
        '<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only">Deleting ...</span>'
      );
      $.ajax({
        type: 'GET',
        url: 'index.php?action=ajax&ajax=att&ajaxaction=delete',
        data: { attId: attachmentId, csrf: csrf },
        success: function (msg) {
          $('.att_' + attachmentId).fadeOut('slow');
          $('#pmf-admin-saving-data-indicator').html('<p class="alert alert-success">' + msg + '</p>');
        },
      });
    }
  });
});
