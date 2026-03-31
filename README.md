Sidama COC Verification System

A web-based prototype designed to digitize and secure the Certificate of Competence (COC) verification process for the Sidama Region, Ethiopia. This project addresses the critical challenges of certificate forgery and the inefficiencies of manual, paper-based verification.
🚀 Key Features

    Public Verification Portal: Allows employers and institutions to instantly verify certificate validity using a unique Roll ID.

    Administrative Dashboard: Comprehensive management of student records, certificate statuses, and system analytics.

    Bulk Data Ingestion: Automated CSV/Excel import routine with logical field mapping to handle large datasets efficiently.

    Role-Based Access Control (RBAC): Secure access levels for Super Admins, Admins, and Public Users.

    Status Management: Real-time tracking of certificate states (Valid, Expired, or Revoked).

    Export & Print: Secure generation of verification results in PDF format for official use.

🛠️ Tech Stack

    Backend: PHP 8.x

    Database: MySQL

    Frontend: HTML5, CSS3, JavaScript, Bootstrap 5

    Methodology: Structured System Analysis and Design (SSAD) & BPMN Process Modeling

📂 System Architecture & Logic

The system follows a Three-Tier Architecture:

    Presentation Layer: Responsive web interfaces for users and admins.

    Application Layer: PHP logic handling authentication, CRUD operations, and CSV parsing.

    Data Layer: MySQL database storing relational student and credential data.

Process Flow (BPMN)

The system was designed using rigorous BPMN modeling to transition from the manual "As-Is" process to a streamlined "To-Be" digital workflow.
📸 System Preview
Admin Dashboard

Comprehensive overview of certificate metrics and system health.
Public Verification Result

Clear indicators for Valid vs. Invalid credentials.
Bulk Import Interface

Automated tool for uploading thousands of records simultaneously.
🔧 Installation & Setup

    Clone the Repository:
    Bash

    git clone https://github.com/your-username/sidama-coc-verification.git

    Database Configuration:

        Import database/sidama_coc.sql into your local MySQL server (e.g., via phpMyAdmin).

        Update config/db_connection.php with your local database credentials.

    Local Server:

        Move the project folder to your htdocs (XAMPP) or www (WAMP) directory.

        Access the system via http://localhost/sidama-coc-verification.

📜 Academic Context

This prototype was developed as a Master's Thesis at the Czech University of Life Sciences Prague (CZU), Faculty of Economics and Management.

    Author: Dawit Birru Hurisso

    Supervisor: doc. Ing. Martin Pelikán, Ph.D.

    Department: Information Engineering

📄 License

This project is licensed under the MIT License - see the LICENSE file for details.
