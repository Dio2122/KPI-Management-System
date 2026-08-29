# Employee KPI Management & Evaluation System

A PHP and MySQL web application for managing organisational Key Performance Indicators (KPIs), logging multi-tier numerical evaluations, tracking performance history, computing sectioned weighted scores, and visualising departmental analytics with Chart.js.

---

## Key Features

- **Executive Dashboard:** Live tracking of staff counts, evaluation completion rates, active KPI counts, prioritised action items, and departmental averages.
- **Two-Tier Weighted Evaluation System:**
  - **Section 1: Core Competencies (25% Weightage)** with itemised metric scoring[cite: 33].
  - **Section 2: KPI Achievements (75% Weightage)** with group-level averaging and weighted rollups[cite: 33].
  - **Year-over-Year Growth Tracking:** Automatic calculation of score variations relative to the previous evaluation year[cite: 33].
- **Staff Directory & Profiles:** Searchable and department-filtered employee index featuring modal quick-views, historical performance timelines, and competency radar charts[cite: 29, 31].
- **KPI Master List Management:** Dynamic management of sections, metric groups, and KPI definitions with referential integrity deletion protection[cite: 21, 28].
- **Interactive Visual Analytics:** 
  - **Radar Charts:** Multi-axis competency and skill analysis[cite: 23, 29].
  - **Trend Lines:** Multi-year performance tracking[cite: 23].
  - **Distribution Doughnut & Department Bars:** Visual breakdowns of top performers, at-risk staff, and departmental benchmarks[cite: 23].
- **Automated Executive Summaries:** Generated narrative insights identifying key strengths, development areas, and trend directions[cite: 23].
- **Reporting & Export:** Printable formal annual evaluation sheets and custom CSV data exports[cite: 25, 33].
- **Client-Side Table Sorting:** Custom JavaScript module for multi-column alphabetical and numeric table sorting[cite: 32].

---

## System Architecture & Database Schema

The application is built with vanilla PHP using PDO prepared statements connected to a MySQL database (`employee_kpi_system`):