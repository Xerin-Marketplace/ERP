# Phase 1 — Database Schema

## companies (existing)
`id, uuid, code UK, legal_name, trading_name, company_type, parent_company_id FK→companies, registration_number, tin, vat_number, logo, email, phone, website, country, region, district, city, physical_address, postal_address, currency(3), timezone, operational_start_date, status, created_by FK→users, timestamps`

## branches (core + extensions)
`id, name, code UK, city, is_active→dropped`
+ `company_id FK→companies, branch_type, manager_id, contact_person, phone, email, country, region, district, physical_address, postal_address, latitude, longitude, opening_date, description, status, created_by`
**+ Phase 1:** `country_code(2), currency(3), timezone(60), postal_code(20), activated_at, deactivated_at, updated_by FK→users`, index `(country_code, status)`

## departments
`id, department_definition_id FK nullable, code, name, description, company_id FK, branch_id FK nullable, parent_id FK self, head_id, deputy_head_id, email, phone, status, effective_date, created_by, timestamps`
**+ Phase 1:** unique `(branch_id, code)` — NULL branch rows (company-level) exempt.

## branch_settings (new)
`id, branch_id FK→branches cascade, key(100), value(text), type(string|bool|int|json), updated_by FK→users, timestamps`, unique `(branch_id, key)`

## branch_administrators (new)
`id, branch_id FK→branches cascade, user_id FK→users cascade, assigned_by FK→users, revoked_at, revoked_by FK→users, timestamps`, unique `(branch_id, user_id)` — revocation is soft (revoked_at), preserving history.

## job_positions (Employee module — reused, not duplicated)
`id, code UK, title, description, company_id FK, department_id, job_grade_id, reports_to_position_id self-FK, category, minimum_qualifications, responsibilities, status, timestamps`

## locations, department_definitions — unchanged.

Rules enforced: branch code globally unique; dept code unique per branch; every dept→branch→company chain FK-guarded; status changes timestamped; no destructive migration issued.
