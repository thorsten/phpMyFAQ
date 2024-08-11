export const createReport = async (data, csrfToken) => {
  try {
    const response = await fetch('./api/export/report', {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        data: data,
        csrfToken: csrfToken,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    if (response.ok) {
      return await response.blob();
    } else {
      return await response.json();
    }
  } catch (error) {
    console.error(error);
  }
};
