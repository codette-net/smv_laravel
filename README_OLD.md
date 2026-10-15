# SMV Platform Rebuild

## Overview

This repository is part of the Sales en Marketing Vacatures (SMV) platform rebuild.

The project is split into two separate repositories:

- `smv_wp`
  - WordPress frontend
  - marketing pages
  - design and content management
  - acts as the public website layer

- `smv_laravel`
  - Laravel backend application
  - job board logic
  - feeds/imports
  - employer flows
  - future CRM and automation integrations

The goal is to gradually move core business logic away from a plugin-heavy WordPress setup into a more maintainable Laravel architecture.

---

## Current Stack

### WordPress
- WordPress
- Custom theme/frontend
- Elementor (temporary / partial usage)
- Existing job board functionality

### Laravel
- Laravel 12
- Filament
- Spatie packages
- Queue system
- Future API integrations

---

## High Level Architecture

```text
Visitors
    ↓
WordPress frontend (smv_wp)
    ↓
Laravel backend (smv_laravel)
    ↓
Jobs / Imports / Employer logic
```

WordPress is responsible for:
- public pages
- landing pages
- branding
- SEO content

Laravel is responsible for:
- jobs
- feeds/imports
- employer functionality
- automation
- APIs
- future integrations

---

## Planned Features

### Phase 1
- Stable rebuild of current platform
- Multiple feed imports
- Job listing pages
- Employer job submissions
- Basic payment flow
- Filament admin panel
- Improved maintainability

### Phase 2
- CRM integrations
- Newsletter flows
- Social media automation
- Analytics improvements
- Better employer tooling

### Phase 3
- Advanced automation
- Candidate flows
- Reporting
- Marketing integrations
- Subscription/payment improvements

---

## Repository Structure

### smv_wp
Handles:
- WordPress frontend
- theme
- templates
- marketing pages
- SEO pages

### smv_laravel
Handles:
- jobs
- imports
- employer dashboard
- APIs
- integrations
- admin tooling

---

## Goals

- Reduce plugin dependency
- Improve stability
- Improve maintainability
- Make imports reliable
- Prepare platform for scaling
- Separate frontend and business logic
- Reduce operational overhead

---

## Notes

The current production platform is still active during rebuild.

Development happens incrementally while maintaining business continuity.