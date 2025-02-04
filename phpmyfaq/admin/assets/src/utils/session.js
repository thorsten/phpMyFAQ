import { Modal } from 'bootstrap';

export const handleSessionTimeout = () => {
  const showWarning = top.document.getElementById('pmf-show-session-warning');
  const config = { attributes: true };
  if (showWarning) {
    const observer = new MutationObserver(onAttributeChange);
    observer.observe(showWarning, config);
    reloadCurrentPage();
  }
};

const onAttributeChange = (mutationsList) => {
  for (let mutation of mutationsList) {
    if (mutation.type === 'attributes' && mutation.attributeName === 'data-value') {
      const value = mutation.target.getAttribute('data-value');
      toggleSessionWarnungModal(value);
    }
  }
};
const toggleSessionWarnungModal = (toggle) => {
  const sessionWarnungModal = new Modal(top.document.getElementById('sessionWarningModal'));
  if (toggle === 'show') {
    sessionWarnungModal.show();
  } else {
    sessionWarnungModal.hide();
  }
};

const reloadCurrentPage = () => {
  const reloadButton = document.getElementById('pmf-button-reload-page');
  if (reloadButton) {
    reloadButton.addEventListener('click', () => {
      location.reload();
    });
  }
};
