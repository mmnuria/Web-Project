# Classroom Reservation Management - Web Project

Web application developed as an individual project for the **Web Technologies** course in the Computer Engineering Degree at the University of Granada. This tool allows the management of classroom reservations in an educational center, with different functionalities depending on the user type.

## Author

- **Nuria Manzano Mata**
- Grade: 9

---

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript  
- **Backend**: PHP (no frameworks)  
- **Database**: MySQL  
- **Tools**: Figma (UI design), phpMyAdmin (DB management)

---

## Main Features

- **Room visualization** (name, capacity, location, photos, description)
- **Reservation management**:
  - Create, view, and cancel reservations
  - Advanced search with filters
- **User system**:
  - New user registration with validation
  - Login/logout with role management (anonymous, user/client, administrator)
- **Comments** on rooms
- **Admin panel**:
  - Full CRUD operations on users and rooms
  - System log management
  - Database backup

---

## Application Design

- **Intuitive and minimalist** style
- Adaptive navigation based on user role
- Modular structure with reusable components
- Sidebar only visible on `index.php` to avoid visual clutter on other pages

Initially designed using Figma to ensure visual coherence across views.

---

## Notable Technical Features

- **Modularized code**, including reusable functions (e.g., trimming seconds from time fields)
- **Validated forms** on both client and server sides
- **Reusable components** across multiple views

---

## Optional Items Implemented

- JavaScript:
  - Image display and modal (`index.php`, `salas.php`)
  - Scroll-to-top button (`editar_sala.php`)
  - Form validation
