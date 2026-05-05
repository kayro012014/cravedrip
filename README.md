# ☕ CraveDrip Coffee Shop

A complete coffee shop website with landing page + (upcoming) POS & Inventory System.

**Author:** Jude Christian Rojas  
**Tech Stack:** HTML5, CSS3, JavaScript (Frontend) | PHP, MySQL (Backend - coming soon)

---

## 📁 Folder Structure

```
cravedrip/
│
├── index.html              ← Main landing page
│
├── css/
│   └── style.css           ← All styling (organized by section)
│
├── js/
│   └── script.js           ← All interactive features
│
├── assets/
│   └── images/             ← Put your local images here later
│
└── README.md               ← This file
```

---

## 🎨 Customization Guide

### Change Colors (Easy!)

Open `css/style.css` and find the `:root` section at the top.  
All colors are CSS variables — change them once and they update everywhere:

```css
:root {
    --color-cream: #f5efe6;        /* Background */
    --color-coffee: #3d2817;       /* Dark brown */
    --color-espresso: #1a0f08;     /* Almost black */
    --color-latte: #c4a484;        /* Light brown */
    --color-caramel: #b8763a;      /* Orange accent */
    --color-cream-dark: #e8dfd0;   /* Section bg */
    --color-text: #2a1810;         /* Text color */
}
```

### Change Fonts

The site uses **Playfair Display** (headings) + **DM Sans** (body).  
To change them:
1. Visit [Google Fonts](https://fonts.google.com)
2. Pick new fonts and copy the import URL
3. Replace the `<link>` tag in `index.html`
4. Update `--font-display` and `--font-body` in `style.css`

### Replace Images

Currently using Unsplash URLs. To use your own images:
1. Save images in `assets/images/`
2. In `index.html`, replace the URLs like:
   ```html
   <!-- From: -->
   <img src="https://images.unsplash.com/...">
   
   <!-- To: -->
   <img src="assets/images/coffee-hero.jpg">
   ```

### Edit Menu Items

In `index.html`, find the `<!-- MENU SECTION -->` and look for `<div class="menu-item">` blocks.  
Each item has:
- An image
- A category (`data-category="coffee"`)
- A name, description, and price
- An optional badge (Bestseller, New, Fresh)

Just copy/paste a block to add more items!

### Edit Contact Info

Find the `<!-- CONTACT SECTION -->` in `index.html` and update:
- Location
- Hours
- Phone number

### Change the Logo Style

The logo "Crave**Drip**" splits the name into two parts using a `<span>`.  
In `index.html`, look for:
```html
<div class="logo">Crave<span>Drip</span></div>
```
The `<span>` part is styled differently (italic + caramel color) — you can swap which half is highlighted!

---

## 🔧 CSS Sections (Quick Reference)

| Section | What it controls |
|---------|------------------|
| `1. CSS Variables` | Colors, fonts, transitions |
| `2. Global Reset` | Default styles for all elements |
| `3. Navigation Bar` | Top menu, logo, mobile hamburger |
| `4. Hero Section` | Main banner with image |
| `5. About Section` | Story + statistics |
| `6. Menu Section` | Product grid + category filters |
| `7. Features Section` | "Why Choose Us" cards (dark bg) |
| `8. Contact Section` | Contact form + info |
| `9. Footer` | Bottom links and social icons |
| `10. Animations` | Scroll reveals, floating effects |
| `11. Responsive` | Mobile/tablet adjustments |

---

## ⚙️ JavaScript Features

| Feature | Description |
|---------|-------------|
| Navbar Scroll | Background appears when scrolling |
| Mobile Menu | Hamburger toggle for small screens |
| Category Filter | Show/hide menu items by type |
| Scroll Reveal | Elements fade in on scroll |
| Contact Form | (Placeholder — connects to PHP later) |
| Add to Cart | (Placeholder — connects to POS later) |

---

## 🚀 How to Run

**Option 1: Direct Browser**
Just double-click `index.html` — works immediately!

**Option 2: With XAMPP (Recommended for later)**
1. Place the `cravedrip` folder inside `C:\xampp\htdocs\`
2. Start Apache in XAMPP Control Panel
3. Visit: `http://localhost/cravedrip/`

---

## 📝 Coming Soon

- [ ] PHP backend integration
- [ ] MySQL database connection
- [ ] Dynamic menu loading from database
- [ ] Admin login page
- [ ] POS / Cashier interface
- [ ] Inventory management
- [ ] Sales reports & dashboard
- [ ] PDF receipt generation

---

## 💡 Tips for Tweaking

1. **Always backup before big changes** — copy the folder first
2. **Use browser DevTools (F12)** — inspect elements to see what to change
3. **Test on mobile** — resize browser or use responsive mode (Ctrl+Shift+M)
4. **Comments are your friend** — every section is labeled in the code

---

Made with ☕ for portfolio building.
