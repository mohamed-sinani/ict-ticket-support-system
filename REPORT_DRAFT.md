# INSTITUTIONAL ICT SUPPORT TICKET & ISSUE TRACKING SYSTEM

## DECLARATION
I, ______________________________, declare that this project report titled “Institutional ICT Support Ticket & Issue Tracking System” is my own original work and has not been submitted to any other institution for academic award.

This report has been prepared based on the implemented web-based system, which provides employee registration, authentication, ICT issue reporting, ticket tracking, ticket assignment, reporting and analytics, email notifications, and AI-assisted support access.

Candidate Name: ______________________________

Registration Number: ______________________________

Supervisor Name: ______________________________

Signature: ______________________________

Date: ______________________________

## ACKNOWLEDGEMENT
I thank Almighty God for the strength and wisdom to complete this project.

I also express my sincere appreciation to my supervisor, lecturers, colleagues, and anyone who supported the development of this project through guidance, advice, and encouragement.

Special thanks go to the users who would benefit from this system, especially employees, ICT staff, and administrators, whose daily support workflows informed the design of the application.

## ABSTRACT
Institutions often receive ICT support requests through informal channels such as phone calls, direct visits, emails, and messaging applications. These methods make it difficult to keep proper records, assign tasks efficiently, monitor request status, and generate useful reports. To address this challenge, this project developed a web-based Institutional ICT Support Ticket and Issue Tracking System that centralizes account registration, authentication, request submission, ticket assignment, tracking, reporting, and automated notifications.

The system was developed using PHP for server-side processing, MySQL for database management, and HTML, CSS, and JavaScript for the user interface. The solution supports three main user roles: Employees, ICT Staff, and Administrators. Employees can register, log in, submit ICT issues, and track request progress using unique ticket tracking codes. ICT Staff can view assigned requests, update ticket status, and record resolution progress. Administrators can review registrations, assign tickets, manage system data, and monitor performance through dashboards and reports.

The application also includes an email notification module that sends automated alerts during registration review, ticket submission, assignment, and resolution updates. In addition, an AI-powered assistant has been integrated to support conversational access to system information and user guidance. The completed system improves accountability, transparency, and response coordination in ICT service delivery.

## LIST OF ABBREVIATIONS
ICT - Information and Communication Technology
DBMS - Database Management System
GUI - Graphical User Interface
NFR - Non-Functional Requirement
FR - Functional Requirement
OTP - One-Time Password

## TABLE OF CONTENTS
1. Chapter One: Introduction
2. Chapter Two: Literature Review
3. Chapter Three: Methodology
4. Chapter Four: System Analysis and Requirements
5. Chapter Five: System Design
6. Chapter Six: System Development, Implementation and Testing
7. Conclusion and Recommendation
8. References
9. Appendix

## CHAPTER ONE: INTRODUCTION

### 1.1 Background Information
ICT services are essential in modern institutions because they support communication, record keeping, internet access, printing, software use, and daily administrative operations. When ICT equipment or services fail, employees often need fast support to avoid delays in work processes.

In many workplaces, ICT requests are still reported through informal methods such as phone calls, physical visits, emails, and chat messages. Although these methods may deliver quick communication, they do not provide reliable ticket records, proper assignment workflows, progress tracking, or automated reporting. This creates delays, accountability gaps, and difficulty in measuring ICT service performance.

This project addresses that challenge by providing a web-based support ticket system for Employees, ICT Staff, and Administrators. The system allows employees to register, log in securely, submit ICT issues with evidence, and track request progress. ICT staff can manage assigned requests, while administrators can monitor the whole process through reports and dashboards.

### 1.2 Problem Statement
The current manual or informal handling of ICT support requests makes it difficult for institutions to keep accurate records of complaints, assign tickets quickly, follow up on unresolved issues, and generate meaningful reports. Users may lose confidence in the support process because they cannot easily know whether their request has been received, assigned, or resolved.

There is therefore a need for a centralized digital system that can manage registration, authentication, service request submission, ticket tracking, reporting, and notifications in a structured and secure way.

### 1.3 Project Objectives

#### 1.3.1 Main Objective
To design and implement a web-based Institutional ICT Support Ticket and Issue Tracking System that improves ICT service delivery through structured registration, request management, tracking, reporting, and notifications.

#### 1.3.2 Specific Objectives
To develop user registration, authentication, and role-based access control module for Employees, ICT Staff, and Administrators.

To develop a web-based ICT service request module that enables employees to categorize and submit ICT-related issues efficiently.

To implement a ticket management and tracking module enabling Administrators to receive, assign, and manage workflows, while allowing users to track request status using unique ticket IDs.

To develop a reporting and analytics module providing dashboards and reports on ticket volume, status, resolution, and user activity, enhanced by an AI-powered assistant for conversational access to system metrics.

To develop an email notification module that sends automated alerts to Admin, ICT staff and employees regarding ticket submission, assignment, status updates, and resolution progress.

### 1.4 Significance of the Project
The system improves ICT support delivery by reducing dependence on informal communication channels. It gives employees a clear process for submitting requests, helps ICT staff work from assigned queues, and allows administrators to supervise support operations from a central dashboard.

It also improves transparency because every ticket has a unique tracking code and a recorded timeline. In addition, the notification module and AI assistant improve user experience by making the system easier to use and follow.

### 1.5 Scope of the Project
The system covers employee registration, secure login with OTP verification, role-based access control, ICT issue submission, evidence attachment, ticket assignment, ticket tracking, resolution updates, dashboards, reports, email notifications, and AI-assisted support guidance.

The system does not cover physical repair work, inventory management, payroll integration, or integration with external enterprise platforms beyond the implemented email and AI features.

### 1.6 Organization of the Report
Chapter One introduces the project background, problem statement, objectives, significance, and scope.

Chapter Two discusses the literature review and the proposed solution.

Chapter Three presents the methodology used in developing the system.

Chapter Four describes the system analysis and requirements.

Chapter Five covers the system design, including architecture and database structure.

Chapter Six explains implementation, testing, and the main system modules.

## CHAPTER TWO: LITERATURE REVIEW

### 2.1 Introduction
This chapter reviews the support practices and concepts related to ICT issue reporting, ticket tracking, user authentication, reporting, notifications, and AI-assisted system support.

### 2.2 Existing Systems and Practices
In many institutions, ICT support is handled using informal channels such as phone calls, direct visits, email messages, and messaging applications. Some organizations also use helpdesk tools or spreadsheets, but these may not provide full workflow control, role management, or integrated reporting.

### 2.3 Limitations of Existing Practices
The main limitations of informal support handling include lost requests, poor follow-up, weak accountability, delayed responses, and limited reporting. Manual methods also make it difficult to measure how many requests are submitted, how quickly they are resolved, and which departments generate the most issues.

### 2.4 Proposed System
The proposed system solves these problems through a centralized web application. It provides user registration, authentication, role-based access, ticket submission, assignment, tracking, reporting, notifications, and AI-based guidance. The system stores all ticket activity in a MySQL database, ensuring that requests can be traced from submission to resolution.

### 2.5 Improvements Introduced by the System
The system introduces structured ticket IDs, automated notifications, evidence attachment, status timelines, dashboards, CSV reporting, and an AI assistant for conversational support. These features improve visibility, accountability, and management of ICT service requests.

## CHAPTER THREE: METHODOLOGY

### 3.1 Introduction
This chapter describes the approach used to collect requirements, analyze the problem, and develop the system.

### 3.2 Development Method
The project followed an iterative prototype-based approach. This allowed the user interface, workflows, and features to be refined step by step as the system evolved.

### 3.3 Reasons for Using This Method
The prototype approach was suitable because the system required user feedback on important workflows such as registration, ticket submission, tracking, and admin handling. It also allowed the interface and module behavior to be improved before finalizing the application.

### 3.4 Phases of Development
Requirement gathering and analysis

Quick design of the interface and database structure

Prototype implementation of core modules

Testing and correction of major workflows

Refinement based on feedback

Final implementation and integration

### 3.5 Data Collection
The project relied mainly on observation of existing support practices and review of user needs from the target environment. The main focus was to identify pain points in current ICT request handling and design a solution around them.

## CHAPTER FOUR: SYSTEM ANALYSIS AND REQUIREMENTS

### 4.1 User Roles
Employee

ICT Staff

Administrator

### 4.2 Functional Requirements
The system shall allow employees to register using official details.

The system shall allow users to log in based on their role.

The system shall verify login using an OTP code sent by email.

The system shall allow employees to submit ICT issues with evidence.

The system shall generate unique tracking codes for tickets.

The system shall allow employees to track ticket progress using the tracking code.

The system shall allow administrators to assign tickets to ICT staff.

The system shall allow ICT staff to update ticket status and add notes.

The system shall provide dashboards and reports for administrators.

The system shall send automated email notifications for key ticket events.

The system shall include an AI assistant for guidance and system metrics access.

### 4.3 Non-Functional Requirements
The system shall be web-based and accessible through a browser.

The system shall use secure password hashing.

The system shall implement role-based access control.

The system shall validate inputs to reduce invalid data.

The system shall store records reliably in a central database.

The system shall provide a responsive interface for different screen sizes.

The system shall present clear error messages and confirmations.

## CHAPTER FIVE: SYSTEM DESIGN

### 5.1 Introduction
This chapter presents the design of the proposed system in terms of architecture, database structure, workflow, and interface organization.

### 5.2 System Architecture
The system uses a browser-based architecture where users interact with the web application through HTML pages and JavaScript forms. PHP handles server-side processing, MySQL stores the data, and email services are used for notifications.

### 5.3 Main Modules
User registration and approval

Authentication and OTP verification

Employee issue reporting

Ticket assignment and workflow management

Ticket tracking and timeline display

Dashboards and analytics

Email notifications

AI assistant support

### 5.4 Database Design
The database contains tables for users, departments, categories, subcategories, tickets, comments, attachments, notifications, OTP codes, and password reset tokens. The schema supports ticket history, evidence storage, user approval, and notification records.

### 5.5 Interface Design
The system includes a public report page, tracking page, login page, registration page, and role-based dashboards for employees, ICT staff, and administrators. The detailed user interface discussion can be added separately with screenshots.

## CHAPTER SIX: SYSTEM DEVELOPMENT, IMPLEMENTATION AND TESTING

### 6.1 Introduction
This chapter describes the technologies and major implementation features used in the system.

### 6.2 Development Tools and Technologies
PHP

MySQL

HTML, CSS and JavaScript

AJAX and JSON APIs

Email notification integration

AI assistant integration through an external model API

### 6.3 Implementation Summary
The registration module allows employees to create accounts using official details. New accounts are reviewed by administrators before login access is granted.

The login module uses email and password authentication, followed by email OTP verification.

The report module allows employees to verify their employee number, choose issue category and subcategory, attach evidence, and submit a ticket.

The admin module supports ticket assignment, user approvals, dashboards, and reports.

The staff module allows ICT staff to monitor assigned tickets and update their status.

The tracking module allows users to search ticket status using a unique tracking code.

The notification module sends automated emails for registration approval, ticket creation, assignment, and resolution updates.

The AI module provides conversational assistance for system-related queries and metrics access.

### 6.4 Testing
The main workflows were tested to confirm that registration, login, ticket submission, tracking, assignment, status update, notification delivery, and reporting work as expected.

### 6.5 Security Controls
The system uses password hashing, CSRF protection, role checks, input validation, and session-based authentication to improve security.

## CONCLUSION AND RECOMMENDATION
The Institutional ICT Support Ticket & Issue Tracking System provides a structured way to manage ICT service requests in an institution. It reduces manual handling, improves communication, and strengthens accountability through ticket tracking, reports, and notifications.

It is recommended that the system be extended in future to include mobile application support, SMS notifications, advanced analytics, and integration with asset management if needed.

## REFERENCES
Add the final references used in your literature review, textbooks, articles, and online sources.

## APPENDIX
Add screenshots of the login page, registration page, report form, tracking page, admin dashboard, staff dashboard, and employee dashboard here.
