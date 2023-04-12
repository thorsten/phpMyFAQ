import { addElement, serialize } from '../../../../assets/src/utils';

export const handleDeleteComments = () => {
  const deleteFaqComments = document.getElementById('pmf-button-delete-faq-comments');
  const deleteNewsComments = document.getElementById('pmf-button-delete-news-comments');

  if (deleteFaqComments) {
    deleteFaqComments.addEventListener('click', () => {
      deleteComments('faq');
    });
  }

  if (deleteNewsComments) {
    deleteNewsComments.addEventListener('click', () => {
      deleteComments('news');
    });
  }
};

const deleteComments = (type) => {
  const responseMessage = document.getElementById('returnMessage');
  const form = document.getElementById(`pmf-comments-selected-${type}`);
  const comments = new FormData(form);

  fetch('index.php?action=ajax&ajax=comment', {
    method: 'POST',
    headers: {
      Accept: 'application/json, text/plain, */*',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      type: type,
      data: serialize(comments),
    }),
  })
    .then(async (response) => {
      if (response.ok) {
        return response.json();
      }
      throw new Error('Network response was not ok: ', { cause: { response } });
    })
    .then((response) => {
      if (response.success) {
        const commentsToDelete = document.querySelectorAll('tr td input:checked');
        commentsToDelete.forEach((toDelete) => {
          toDelete.parentNode.parentNode.parentNode.remove();
        });
      } else {
        responseMessage.append(addElement('div', { classList: 'alert alert-danger', innerText: response.error }));
      }
    })
    .catch(async (error) => {
      const errorMessage = await error.cause.response.json();
      responseMessage.append(addElement('div', { classList: 'alert alert-danger', innerText: errorMessage }));
    });
};
