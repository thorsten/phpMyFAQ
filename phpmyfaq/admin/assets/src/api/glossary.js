export const deleteGlossary = async (glossaryId, csrfToken) => {
  try {
    const response = await fetch('./api/glossary/delete', {
      method: 'DELETE',
      headers: {
        Accept: 'application/json, text/plain, */*',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        csrf: csrfToken,
        id: glossaryId,
      }),
    });

    if (response.status === 200) {
      return await response.json();
    } else {
      throw new Error('Network response was not ok.');
    }
  } catch (error) {
    console.error('Error deleting FAQ: ', error);
    throw error;
  }
};
