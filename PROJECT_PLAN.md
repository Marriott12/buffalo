# Buffalo Marathon 2025 Website - Project Plan

## Overview
A modern, responsive web application for Buffalo Marathon 2025. Features include user registration/accounts, marathon registration, event schedule, admin module for managing participants and event postings. The marathon is scheduled for **Saturday, 11 October 2025** at **Buffalo Park Recreation Centre, Chalala-Along Joe Chibangu Road**.

---

## Tech Stack (Recommended)
- **Backend:** PHP (Laravel or Slim) / Node.js (Express) / Python (Flask or Django)
- **Frontend:** HTML5, CSS3 (Bootstrap 5), JavaScript (Vanilla or React)
- **Database:** MySQL/PostgreSQL/SQLite
- **Authentication:** Session-based, JWT, or Laravel Auth (if using Laravel)
- **Responsive Design:** Bootstrap 5, Army Green (#4B5320 palette)
- **Notifications:** Email (SMTP), Dashboard alerts

---

## Core Features

### 1. Home Page
- Responsive, modern landing
- Event date, venue, highlights
- Call to action: Register/Login

### 2. User Module
- **Account Registration/Login**
  - Name, email, password, phone
  - Password reset
- **Marathon Registration**
  - Choose category (see below)
  - Category fees auto-calculated
  - Payment instructions (manual or online)
  - View registration status and payment status
- **Categories**
  - 42 KM (Full Marathon)
  - 21 KM (Half Marathon)
  - 10 KM (Power Challenge)
  - 5 KM (Family Fun Run)
  - VIP Run
  - Kid Run
- **Notifications**
  - See payment status
  - Confirmation email on registration/payment
- **View Event Schedule & News/Postings**

### 3. Admin Module
- Secure admin login
- **Manage Participants**
  - List/search/filter participants
  - Update payment status, category
- **Manage Event Schedule, News/Postings**
  - Add/update/delete event schedule items
  - Broadcast notifications (dashboard/email)

### 4. Marathon Registration Form
- Only for logged-in users
- Fields: Name, Age, Gender, Phone, Category, Emergency Contact, T-Shirt Size, etc.
- Fee auto-determined by category
- Payment instructions (with status update by admin)
- Registration deadline: 30 September 2025

### 5. Event Info Pages
- Event schedule (admin editable)
- FAQs
- Location map

### 6. Color Palette
- Army Green: #4B5320 (primary)
- Complementary: #FFFFFF, #EDEDED, #222B1F, #8F9779

---

## Suggested Directory Structure
