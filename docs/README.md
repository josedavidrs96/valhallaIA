# Documentation

This folder contains all project documentation, organized by audience and purpose.

## Folder Structure

```
docs/
├── business/        # Business overview and strategy
├── working_docs/    # Working documents (requirements, designs, tasks)
├── users_manuals/   # End-user documentation
├── support/         # Support and troubleshooting
├── development/     # Developer documentation
├── deployment/      # Deployment guides
└── marketing/       # Marketing materials
```

---

## 📊 Business (`/business`)

High-level business documentation for stakeholders and product understanding.

**Required Files:**
- `overview.md` - **MANDATORY** - High-level business overview of the project. Must exist before development starts.

**Additional Contents:**
- Product vision and roadmap
- Feature specifications
- Domain glossary
- Stakeholder requirements

**Audience:** Product Owners, Stakeholders, Business Analysts

---

## 📋 Working Documents (`/working_docs`)

Active development documents organized by work type. **All documents for one item are in ONE folder.**

**Structure:**
```
working_docs/
├── epics/           # Large initiatives with full business justification
│   └── [epic-name]/
│       ├── requirements.md
│       ├── validation.md
│       ├── design.md
│       └── tasks.md
├── features/        # Features linked to epics
│   └── [feature-name]/
├── hotfixes/        # Urgent fixes for production issues
│   └── [hotfix-name]/
└── cases/           # Incident analysis (investigation only)
    └── [case-id]/
```

**Workflow:** See `/docs/development/development_process.md`

**Audience:** Product Engineers, Developers, Support

---

## 📖 User Manuals (`/users_manuals`)

Detailed guides for end-users on how to use the application.

**Contents:**
- Getting started guides
- Feature walkthroughs
- FAQ for users
- Screenshots and tutorials

**Maintenance:** Should be updated after each feature release or UI change.

**Audience:** End Users, Customer Success

---

## 🛠 Support (`/support`)

Support documentation for troubleshooting and issue resolution.

**Contents:**
- Troubleshooting guides
- FAQ for support team
- Common issues and solutions
- Bug identification procedures
- Support case analysis
- Escalation procedures

**Audience:** Support Team, DevOps

---

## 💻 Development (`/development`)

Technical documentation for developers.

**Contents:**
- Onboarding guide for new developers
- Local environment setup
- Coding standards and conventions
- API documentation
- Database schema
- Testing guidelines

**Audience:** Developers, Tech Leads

**Note:** For architecture documentation, see `/ai_docs/architecture/`

---

## 🚀 Deployment (`/deployment`)

Guides for deploying and maintaining the application in different environments.

**Contents:**
- Deployment procedures
- Environment configuration
- CI/CD pipeline documentation
- Rollback procedures
- Infrastructure overview

**Audience:** DevOps, Developers

---

## 📢 Marketing (`/marketing`)

Marketing materials and messaging.

**Contents:**
- Product descriptions
- Feature highlights
- Marketing copy
- Screenshots for marketing

**Purpose:**
- Validates that features align with marketing promises
- Ensures coherence between product and communication
- Reference for new feature announcements

**Audience:** Marketing Team, Product Owners

---

## Documentation Guidelines

### Writing Standards
- Use clear, concise language
- Include examples where helpful
- Keep documentation up to date with code changes
- Use screenshots for UI-related documentation

### Update Triggers
| Documentation | Update When |
|--------------|-------------|
| User Manuals | After UI changes or new features |
| Development | After architecture changes |
| Deployment | After infrastructure changes |
| Support | After discovering new issues |
| Business | After product strategy changes |
| Marketing | After new feature releases |

### File Naming
- Use lowercase with hyphens: `getting-started.md`
- Be descriptive: `user-authentication-guide.md` not `auth.md`
