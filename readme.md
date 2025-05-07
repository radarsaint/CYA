# Children of Yves: Arcania (CYA)

A web‑based RPG framework inspired by tabletop mechanics.  
Build a character, manage stats, and launch into tactical combats and narrative “adventures” using PHP, MySQL and a bit of JavaScript.

---

## ⚙️ Features

- **Character Creation Wizard**  
  • Races, Wellsprings, Corporea/Essentia Foci  
  • Dispositions, Awakening Story, Saga & Mask  
  • Point‑buy for Luck / Speed / Endurance  
  • Auto‑seeded pools & tokens: HP, Luck, Adventure Tokens, Golden Dollars, XP, Level  
- **REST‑style API Endpoints**  
  • `api/config.php?type=…` → dropdown data (races, masks, etc.)  
  • `api/start_combat.php` → roll Speed vs. monster, init BM/AM & HP  
  • `api/take_action.php` → spend BM, Luck, HP, resolve actions  
  • `api/start_adventure.php` → spend an Adventure Token  
  • `api/reset_tokens.php` → reset daily Adventure Tokens  
- **MySQL Schema & Migrations**  
  • `migrations/001_add_character_fields.sql` defines the core table changes  
  • Optional baseline in `sql/initial_schema.sql`  
- **Simple Front‑end**  
  • Character sheet (HTML + vanilla JS)  
  • Dashboard & login/register pages  

---

## 📦 Prerequisites

- PHP ≥ 7.4 with PDO & MySQL extensions  
- MySQL or MariaDB  
- Composer  
- Web server (Apache, Nginx, or PHP’s built‑in server)  

---

## 🚀 Installation

1. **Clone & enter**  
   ```bash
   git clone https://github.com/radarsaint/CYA.git
   cd CYA
