export const handleFaqOverview = async () => {
  const deleteFaqButtons = document.querySelectorAll('.pmf-button-delete-faq');
  let result;

  if (deleteFaqButtons) {
    deleteFaqButtons.forEach((element) => {
      element.addEventListener('click', (event) => {
        event.preventDefault();

        const faqId = event.target.getAttribute('data-pmf-id');
        const faqLanguage = event.target.getAttribute('data-pmf-language');
        const token = event.target.getAttribute('data-pmf-token');

        if (confirm('Are you sure?')) {
          fetch('index.php?action=ajax&ajax=records&ajaxaction=delete_record', {
            method: 'DELETE',
            headers: {
              Accept: 'application/json, text/plain, */*',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              csrf: token,
              record_id: faqId,
              record_lang: faqLanguage,
            }),
          })
            .then(async (response) => {
              if (response.status === 200) {
                return response.json();
              }
              throw new Error('Network response was not ok: ', { cause: { response } });
            })
            .then((response) => {
              const result = response;
              if (result.success) {
                const faqTableRow = document.getElementById(`record_${faqId}_${faqLanguage}`);
                faqTableRow.remove();
              } else {
                console.error(result.error);
              }
            });
        }
      });
    });
  }
};
