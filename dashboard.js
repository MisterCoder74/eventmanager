document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => card.classList.add('shadow-sm'));
        card.addEventListener('mouseleave', () => card.classList.remove('shadow-sm'));
    });
});
