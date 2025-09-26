# LYCA: AI-Powered Emergency and Self-Diagnosis Management System

##  Project Overview
LYCA is a web-based system designed to improve **emergency response** and provide **AI-powered self-diagnosis** for users.  
The system integrates:
-  **Ambulance requests**
-  **Incident & outbreak reporting**
-  **Emergency news and updates**
-  **AI-driven self-diagnosis** (preliminary health guidance)

This MVP demonstrates how communities can leverage technology to save time, improve coordination, and empower individuals to make informed health decisions before professional help arrives.

---

##  Features
- **User Reporting**: Report incidents, outbreaks, and emergencies in real-time.  
- **Ambulance Services**: Request an ambulance through the system with location details.  
- **AI Self-Diagnosis**: Get preliminary guidance by entering symptoms (AI-powered).  
- **Health News & Alerts**: Access verified health and emergency updates.  
- **Database Integration**: Secure storage of incidents, outbreaks, and reports.  

---

##  Tech Stack
- **Frontend**: HTML, CSS, JavaScript and other few languages  
- **Backend**: PHP  
- **Database**: MySQL  
- **AI Module**: Integrated API for self-diagnosis 

---

##  Project Structure

LYCA/
│── admin_create_post.php
│── ai-diagnosis.php
│── ambulance_index.php
│── ambulance_request.php
│── blog.php
│── blog_index.php
│── db.php
│── emergency.php
│── incident.php
│── incidents_index.php
│── index.php
│── news.php
│── news_index.php
│── process_emergency.php
│── report_ambulance.php
│── report_incident.php
│── report_outbreak.php
│── save_key.php
│── seed_ambulance.php
│── submit-outbreak.php
│── subscribe.php
│── traffic.php
│── lyca.sql # Database schema
│
├── images/ # System images and logo
├── uploads/ # Uploaded incident & outbreak files


---

##  Installation & Setup
1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/LYCA.git
   cd LYCA
Setup database

Import lyca.sql into your MySQL database.

Update db.php with your database credentials.

Run locally

Place the project folder in your server root (e.g., htdocs for XAMPP).

Start Apache & MySQL from XAMPP/WAMP.

Open in browser:
