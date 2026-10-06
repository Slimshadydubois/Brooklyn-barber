# Project Styles and Patterns - Barbearia System

This document defines the visual and technical standards for the Barbearia System. These rules ensure consistency across the application.

## 1. Visual Identity

### Color Palette
- **Primary (Gold):** `#C5A059` (Used for highlights, buttons, and titles)
- **Background (Dark):** `#121212` (Main background)
- **Card Background:** `#1e1e1e`
- **Text Primary:** `#FFFFFF`
- **Text Secondary:** `#BDBDBD`
- **Navigation Background:** `rgba(18, 18, 18, 0.95)`

### Typography
- **Headings (h1, h2, h3) & Brand:** `'Playfair Display', serif`
- **Body Text:** `'Montserrat', sans-serif`
- **Default Line Height:** `1.6`

### UI Components
- **Buttons:** 
  - Primary: Gold background, black text, uppercase, letter-spacing `2px`.
  - Hover: Background `#d4af37`, slight lift (`translateY(-3px)`), shadow.
- **Transitions:** `all 0.3s ease`
- **Sections:** Standard padding `100px 10%`.

## 2. Technical Standards

### Database Conventions
- **Naming:** All tables and columns MUST be in **lowercase**.
- **Language:** Names are in **Portuguese** (e.g., `cliente`, `agendamento`, `servico`).
- **Primary Keys:** Always named `id`, integer, auto-increment.
- **Foreign Keys:** Pattern `id_[table_name]` (e.g., `id_cliente`).

### PHP & Backend
- **Database Access:** Use **PDO** for database connections.
- **Charset:** `utf8mb4`
- **Error Handling:** Use `try-catch` blocks; avoid displaying detailed errors in production.

### File Structure
- **Assets:** Organized into `assets/css/`, `assets/js/`, and `assets/img/`.
- **Database Scripts:** Located in `db/`.
- **Logic/Connections:** Located in `includes/`.

## 3. Mandatory Directive
**YOU MUST ALWAYS FOLLOW THE RULES IN THIS FOLDER FOR ANY MODIFICATION OR ADDITION TO THE CODEBASE.**
