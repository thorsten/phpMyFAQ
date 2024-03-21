export const fetchCaptchaImage = async (action, timestamp) => {
  try {
    const response = await fetch('api/captcha', {
      method: 'POST',
      cache: 'no-cache',
      body: JSON.stringify({
        action,
        timestamp,
      }),
      redirect: 'follow',
      referrerPolicy: 'no-referrer',
    });

    return await response;
  } catch (error) {
    console.error(error);
  }
};
