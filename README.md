# ATLANTICS (shopatlantics.com)

A specialized e-commerce brand website dedicated strictly to premium heavyweight streetwear hoodies (**500 GSM loopback cotton**). Built with high-converting direct WhatsApp checkout and an intuitive flat-file PHP admin backend — engineered to run with **zero external database setup** on **Hostinger Shared Hosting**.

---

## ⚡ Core Features

- **Exclusive Hoodie Catalog**: Showcasing Oversized Pullovers, Two-Way Zip-Ups, and Vintage Mineral Washes with fixed 1:1 ratio image cards that never distort.
- **Dynamic Category & Filter Tabs**: Admin-editable filter buttons with live inventory counts.
- **Interactive Quick-Buy Modal**:
  - Live photo switching on color swatch clicks.
  - Size pills (S, M, L, XL) with size guide guidance.
  - Real-time quantity counter (`−` / `+`) and total price calculation.
- **Automated WhatsApp Checkout**:
  - Tapping **"Order via WhatsApp"** opens WhatsApp directly with a pre-filled, formatted order summary:
    ```text
    Hello! I would like to order *The 500 GSM Archive Pullover* (Oversized Pullovers) 🛍️

    • Selected Color: Washed Onyx
    • Selected Size: L
    • Quantity: 1
    • Total Price: $79.00

    Please confirm availability and share payment & delivery details!
    ```
- **Pakistan Phone Auto-Formatting**: Numbers entered as `03xx-xxxxxxx` automatically convert to international `923xx-xxxxxxx` for seamless WhatsApp routing.
- **Flat-File Admin Backend (`/admin`)**:
  - Add, edit, or delete hoodie models.
  - Manage categories and storefront filter buttons.
  - Update WhatsApp business phone number and prefilled greeting message template.
  - Edit top announcement bar and pricing.
  - Upload photos directly from browser to `uploads/`.
  - Secure bcrypt password authentication with zero MySQL database dependency.
- **100% Responsive**: Built for smartphones, tablets, and desktops.

---

## 🔐 Admin Dashboard Access

- **Local URL**: `http://localhost:8088/admin/login.php`
- **Live URL**: `https://shopatlantics.com/admin/login.php`
- **Default Username**: `admin`
- **Default Password**: `admin123`

*(You can change the admin password anytime in the **"Password & Security"** tab).*

---

## 📦 Hostinger Shared Hosting Deployment

1. Log in to your **Hostinger hPanel** ([hpanel.hostinger.com](https://hpanel.hostinger.com)).
2. Go to **Websites** → Click **Manage** for `shopatlantics.com`.
3. Open **File Manager** and enter the `public_html/` directory.
4. Upload all files from this project into `public_html/`:
   ```text
   public_html/
   ├── index.php
   ├── .htaccess
   ├── README.md
   ├── admin/
   │   ├── index.php
   │   ├── login.php
   │   ├── logout.php
   │   └── auth.php
   ├── includes/
   │   └── functions.php
   ├── data/
   │   ├── product.json
   │   ├── admin.json
   │   └── .htaccess
   ├── uploads/
   └── assets/
       ├── css/
       │   ├── style.css
       │   └── admin.css
       ├── js/
       │   ├── app.js
       │   └── admin.js
       └── images/
           ├── hoodie-black.jpg
           ├── hoodie-bone.jpg
           ├── hoodie-lifestyle.jpg
           ├── hoodie-fabric.jpg
           ├── hoodie-zipup.jpg
           └── hoodie-sage.jpg
   ```
5. Ensure permissions on `data/` and `uploads/` are set to `755` so new images and product edits can be saved.
6. Open `https://shopatlantics.com` in your browser!

---

## 🛠️ Local Development

To run locally using PHP's built-in server:

```bash
# Clone the repository
git clone https://github.com/basharat78/thenorthhoodies.git

# Enter project directory
cd thenorthhoodies

# Start local server
php -S localhost:8088
```

- **Storefront**: [http://localhost:8088](http://localhost:8088)
- **Admin**: [http://localhost:8088/admin/login.php](http://localhost:8088/admin/login.php)

---

## 📄 License

Proprietary © 2026 ATLANTICS. All rights reserved.
