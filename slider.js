let index = 0;
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");
function showSlide(i) {
  slides.forEach((s) => s.classList.remove("active"));
  dots.forEach((d) => d.classList.remove("active-dot"));
  slides[i].classList.add("active");
  dots[i].classList.add("active-dot");
  index = i;
}
function nextSlide() {
  index = (index + 1) % slides.length;
  showSlide(index);
}
function prevSlide() {
  index = (index - 1 + slides.length) % slides.length;
  showSlide(index);
}
document.querySelector(".next").onclick = nextSlide;
document.querySelector(".prev").onclick = prevSlide;
dots.forEach((dot, i) => {
  dot.onclick = () => showSlide(i);
});
setInterval(nextSlide, 5000);
showSlide(index);
