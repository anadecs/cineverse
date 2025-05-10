// Add smooth scrolling to all links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    document.querySelector(this.getAttribute("href")).scrollIntoView({
      behavior: "smooth",
    });
  });
});

// Add hover effect to movie cards
document.querySelectorAll(".movie-card").forEach((card) => {
  card.addEventListener("mouseenter", function () {
    this.style.transform = "translateY(-5px)";
  });

  card.addEventListener("mouseleave", function () {
    this.style.transform = "translateY(0)";
  });
});

// Add confirmation for delete actions
document.querySelectorAll("form[onsubmit]").forEach((form) => {
  form.addEventListener("submit", function (e) {
    if (!confirm("Are you sure you want to delete this review?")) {
      e.preventDefault();
    }
  });
});

// Add rating input validation
const ratingInput = document.querySelector('input[name="rating"]');
if (ratingInput) {
  ratingInput.addEventListener("input", function () {
    const value = parseFloat(this.value);
    if (value < 0) this.value = 0;
    if (value > 10) this.value = 10;
  });
}

// Add search input focus effect
const searchInput = document.querySelector(".nav-search input");
if (searchInput) {
  searchInput.addEventListener("focus", function () {
    this.parentElement.style.boxShadow = "0 0 0 2px rgba(229, 9, 20, 0.2)";
  });

  searchInput.addEventListener("blur", function () {
    this.parentElement.style.boxShadow = "none";
  });
}

// Add loading state to forms
document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", function () {
    const submitButton = this.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Loading...";
    }
  });
});

// Star rating input for review form
function createStarRating() {
  const form = document.querySelector(".review-form");
  if (!form) return;

  // Remove old star rating if exists
  const old = form.querySelector(".star-rating");
  if (old) old.remove();
  const oldLabel = form.querySelector(".star-label");
  if (oldLabel) oldLabel.remove();

  // Create label
  const label = document.createElement("label");
  label.className = "star-label";
  label.textContent = "Rating";

  // Create star rating container
  const starContainer = document.createElement("div");
  starContainer.className = "star-rating";
  for (let i = 1; i <= 5; i++) {
    const star = document.createElement("span");
    star.className = "star";
    star.innerHTML = "&#9733;"; // Unicode star
    star.dataset.value = i;
    star.addEventListener("click", function () {
      form.querySelector('input[name="rating"]').value = i;
      updateStars(i);
    });
    starContainer.appendChild(star);
  }
  // Insert label and star rating at the top of the form
  form.insertBefore(label, form.querySelector(".form-group"));
  form.insertBefore(starContainer, form.querySelector(".form-group"));
  const ratingInput = form.querySelector('input[name="rating"]');
  ratingInput.type = "hidden";
  ratingInput.value = 5;
  updateStars(5);

  function updateStars(val) {
    starContainer.querySelectorAll(".star").forEach((star, idx) => {
      star.style.color = idx < val ? "#f5c518" : "#444";
      star.style.fontSize = "2rem";
      star.style.cursor = "pointer";
      star.style.transition = "color 0.2s";
    });
  }
}

document.addEventListener("DOMContentLoaded", createStarRating);
