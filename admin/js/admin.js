// File: /sistem/admin/js/admin.js

document.addEventListener("DOMContentLoaded", function () {
  // Initialize sections
  initializeSections();

  // Initialize navigation
  initializeNavigation();

  // Initialize menu cards
  initializeMenuCards();

  // Initialize stats cards
  initializeStatsCards();
});

function initializeSections() {
  const sections = document.querySelectorAll(".section-transition");
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("show");
        }
      });
    },
    { threshold: 0.1 }
  );

  sections.forEach((section) => {
    observer.observe(section);
  });
}

function initializeNavigation() {
  const navItems = document.querySelectorAll(".nav-item");
  const currentPath = window.location.pathname;

  navItems.forEach((item) => {
    const link = item.getAttribute("href");
    if (currentPath.includes(link)) {
      item.classList.add("active");
    }

    item.addEventListener("click", function (e) {
      navItems.forEach((nav) => nav.classList.remove("active"));
      this.classList.add("active");
    });
  });
}

function initializeMenuCards() {
  const menuCards = document.querySelectorAll(".menu-card");

  menuCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      const icon = this.querySelector(".card-icon");
      icon.classList.add("rotate-12");
    });

    card.addEventListener("mouseleave", function () {
      const icon = this.querySelector(".card-icon");
      icon.classList.remove("rotate-12");
    });
  });
}

function initializeStatsCards() {
  const statsCards = document.querySelectorAll(".stats-card");

  statsCards.forEach((card, index) => {
    setTimeout(() => {
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 100);
  });
}

// Chart related functions
function updateChart(chart, period) {
  return new Promise((resolve) => {
    chart.data.datasets.forEach((dataset) => {
      // Simulate data update
      dataset.data = dataset.data.map(() => Math.floor(Math.random() * 100));
    });

    chart.update("none");
    setTimeout(resolve, 300);
  });
}

function showLoadingOverlay(show) {
  const overlay = document.querySelector(".loading-overlay");
  if (show) {
    overlay.style.display = "flex";
  } else {
    overlay.style.display = "none";
  }
}

// Export functions for use in other files
window.adminFunctions = {
  updateChart,
  showLoadingOverlay,
  initializeSections,
  initializeNavigation,
  initializeMenuCards,
  initializeStatsCards,
};
