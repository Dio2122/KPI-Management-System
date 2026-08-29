# Employee KPI Management & Evaluation System

A PHP and MySQL web application for managing organisational Key Performance Indicators (KPIs), logging multi-tier numerical evaluations, tracking performance history, computing sectioned weighted scores, and visualising departmental analytics with Chart.js.

---

## Key Features

- **Executive Dashboard:** Live tracking of staff counts, evaluation completion rates, active KPI counts, prioritised action items, and departmental averages.
- **Two-Tier Weighted Evaluation System:**
  - **Section 1: Core Competencies (25% Weightage)** with itemised metric scoring.
  - **Section 2: KPI Achievements (75% Weightage)** with group-level averaging and weighted rollups.
  - **Year-over-Year Growth Tracking:** Automatic calculation of score variations relative to the previous evaluation year.
- **Staff Directory & Profiles:** Searchable and department-filtered employee index featuring modal quick-views, historical performance timelines, and competency radar charts.
- **KPI Master List Management:** Dynamic management of sections, metric groups, and KPI definitions with referential integrity deletion protection.
- **Interactive Visual Analytics:** 
  - **Radar Charts:** Multi-axis competency and skill analysis.
  - **Trend Lines:** Multi-year performance tracking.
  - **Distribution Doughnut & Department Bars:** Visual breakdowns of top performers, at-risk staff, and departmental benchmarks.
- **Automated Executive Summaries:** Generated narrative insights identifying key strengths, development areas, and trend directions.
- **Reporting & Export:** Printable formal annual evaluation sheets and custom CSV data exports.
- **Client-Side Table Sorting:** Custom JavaScript module for multi-column alphabetical and numeric table sorting.

---

## System Architecture & Database Schema

The application is built with vanilla PHP using PDO prepared statements connected to a MySQL database (`employee_kpi_system`):
