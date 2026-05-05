/* ==========================================
   CRAVEDRIP COFFEE SHOP - JAVASCRIPT
   Author: Jude Christian Rojas
   Description: Interactive features for the landing page
   ========================================== */


// ==========================================
// 1. NAVBAR SCROLL EFFECT
// Adds a background to the navbar when user scrolls down
// ==========================================
const navbar = document.getElementById('navbar');

window.addEventListener('scroll', () => {
    // If user has scrolled more than 50 pixels, add 'scrolled' class
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});


// ==========================================
// 2. MOBILE MENU TOGGLE
// Open/close the navigation menu on mobile devices
// ==========================================
const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

menuToggle.addEventListener('click', () => {
    // Toggle the 'active' class to show/hide menu
    navLinks.classList.toggle('active');
    
    // Switch the icon between hamburger (bars) and X (times)
    const icon = menuToggle.querySelector('i');
    if (navLinks.classList.contains('active')) {
        icon.classList.replace('fa-bars', 'fa-times');
    } else {
        icon.classList.replace('fa-times', 'fa-bars');
    }
});

// Close mobile menu automatically when a nav link is clicked
navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        menuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
    });
});


// ==========================================
// 3. MENU CATEGORY FILTER
// Filter menu items by category (Coffee, Tea, Pastry, etc.)
// ==========================================
const categoryButtons = document.querySelectorAll('.category-btn');
const menuItems = document.querySelectorAll('.menu-item');

categoryButtons.forEach(button => {
    button.addEventListener('click', () => {
        // Remove 'active' class from all buttons first
        categoryButtons.forEach(btn => btn.classList.remove('active'));
        
        // Add 'active' class to the clicked button
        button.classList.add('active');
        
        // Get which category was selected
        const category = button.getAttribute('data-category');
        
        // Loop through menu items and show/hide based on category
        menuItems.forEach(item => {
            if (category === 'all' || item.getAttribute('data-category') === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});


// ==========================================
// 4. SCROLL REVEAL ANIMATION
// Fades in elements as the user scrolls down the page
// Uses the IntersectionObserver API (modern & efficient)
// ==========================================
const revealElements = document.querySelectorAll('.reveal');

const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        // When element enters the viewport, add 'active' class
        if (entry.isIntersecting) {
            entry.target.classList.add('active');
        }
    });
}, {
    threshold: 0.15  // Trigger when 15% of element is visible
});

// Observe each reveal element
revealElements.forEach(el => revealObserver.observe(el));


// ==========================================
// 5. CONTACT FORM SUBMISSION
// For now, just shows alert (later will connect to PHP)
// ==========================================
const contactForm = document.getElementById('contactForm');

contactForm.addEventListener('submit', (e) => {
    // Prevent the form from refreshing the page
    e.preventDefault();
    
    // Collect form data
    const formData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        message: document.getElementById('message').value
    };
    
    // TODO: Send this data to PHP backend later using fetch()
    console.log('Form submitted:', formData);
    
    // Show a friendly confirmation message
    alert(`Thanks for reaching out, ${formData.name}! We'll get back to you soon. ☕`);
    
    // Clear the form
    contactForm.reset();
});


// ==========================================
// 6. ADD TO CART (placeholder)
// Will be connected to the POS system later
// ==========================================
document.querySelectorAll('.menu-item-add').forEach(button => {
    button.addEventListener('click', (e) => {
        // Stop the click from triggering parent element clicks
        e.stopPropagation();
        
        // Find the menu item that was clicked
        const item = e.target.closest('.menu-item');
        const itemName = item.querySelector('h3').textContent;
        
        // For now, just show a visual feedback message
        alert(`Added "${itemName}" to your wishlist! 🛒\n(Order feature coming soon)`);
    });
});


// Confirmation log
console.log('☕ CraveDrip landing page loaded successfully!');
