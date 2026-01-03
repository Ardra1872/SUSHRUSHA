
  const hamburger = document.querySelector('.hamburger');
  const navLinks = document.querySelector('.nav-links');
  const navActions = document.querySelector('.nav-actions');

  hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    navActions.classList.toggle('active');
  });

