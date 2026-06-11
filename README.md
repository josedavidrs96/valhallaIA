# AI-Assisted Development Template

> **Note:** This repository contains the code examples from the book [The Broken Telephone](https://thebrokentelephone.com).

A template for building software with AI assistance, where humans guide and AI executes.

## What is This?

This project provides a complete framework for AI-assisted software development, including:
- **Architecture guides** for consistent code structure
- **Specialized AI agents** for each development phase
- **Workflows** for different work types (epics, features, hotfixes)
- **Documentation templates** for requirements, designs, and tasks

## The AI Evolution in Development

Teams typically evolve through these stages when adopting AI:

| Stage | Who | What AI Does |
|-------|-----|--------------|
| 1. Code Completion | Engineers | Autocomplete suggestions |
| 2. Code Blocks | Engineers | Writes functions/methods |
| 3. Full Features | PO + Engineers | Writes features from specs |
| 4. Spec Assistance | Product Engineers | Helps write specs, human refines |
| 5. Spec + Code | Product Engineers | Writes both, human reviews |
| 6. Autonomous + Oversight | Product Engineers | AI leads, human oversees |
| 7. Fully Autonomous | ? | Future state |

**This template targets stages 5-6.**

## Project Structure

```
.
├── README.md                 # You are here
├── ai_docs/                  # AI guidance documentation
│   ├── architecture/         # Architecture patterns & rules
│   ├── agents/               # AI agent definitions
│   └── development_process/  # Process documentation
├── docs/                     # Project documentation
│   ├── business/             # Business overview (MANDATORY)
│   ├── working_docs/         # Active requirements & designs
│   ├── development/          # Developer guides
│   ├── users_manuals/        # End-user documentation
│   ├── support/              # Support & troubleshooting
│   ├── marketing/            # Marketing materials
│   └── deployment/           # Deployment guides
└── src/                      # Application source code
```

## Getting Started

### Prerequisites

1. An AI assistant that can read files and execute commands (Claude, GPT-4, etc.)
2. Understanding of DDD, CQRS, or Hexagonal Architecture (or willingness to learn)
3. A clear business problem to solve

### Step 1: Define Your Business

**MANDATORY:** Create `/docs/business/overview.md` with:
- What the application does
- Who uses it
- Core business rules
- Domain glossary

> AI agents will refuse to work without this file.

### Step 2: Understand the Architecture

Read the architecture guides in `/ai_docs/architecture/`:
- `architecture.md` - Overall structure
- `critical-rules.md` - Must-follow rules
- `development-workflow.md` - How to implement features (backend)
- `frontend-backend-integration.md` - **Full-stack development strategies**

### Step 3: Learn the Agents

Review the AI agents in `/ai_docs/agents/`:

| Agent | Purpose |
|-------|---------|
| 1. Requirement Writer | Creates/completes requirement documents |
| 2. Requirement Validator | Validates requirements are complete |
| 2.5 Requirement Slicing | Splits large requirements (optional) |
| 3. Requirement Design | Designs technical solution |
| 4. Requirement Tasks | Creates implementation tasks |
| 5. Check DoD | Verifies Definition of Done |
| 6. Check Architecture | Validates architecture compliance |
| 7. Check Code Quality | Reviews code quality |
| 8. Check Performance | Identifies performance issues |
| 9. Linter & Compile | Runs static analysis |
| 10. Testing | Runs and analyzes tests |

### Step 4: Follow the Process

See `/docs/development/development_process.md` for complete workflows:
- **Epic:** New major initiative
- **Feature:** Feature in existing epic
- **Hotfix:** Urgent bug fix
- **Case:** Investigation (support)

## Key Principles

### 1. Agents Have Single Responsibilities

Don't map agents to people. Each agent has ONE job:
- Requirement Writer only writes requirements
- Code Quality only reviews quality
- Testing only handles tests

### 2. Analysis Informs, Never Blocks

AI identifies risks and issues but **never blocks** development. The human always decides.

```
✅ "X is missing/risky. Proceed anyway or define first?"
❌ "Cannot proceed until X is defined"
```

### 3. Architecture is Non-Negotiable

This template requires structured architecture (DDD, CQRS, Hexagonal). Without clear patterns:
- AI makes inconsistent decisions
- Code quality degrades over time
- Technical debt accumulates faster

If you use MVC without layers, this template won't help much.

### 4. Documentation Stays Current

After every production deployment:
- Update user manuals if UI changed
- Update marketing if new features
- Update support docs if new scenarios

## Architecture Overview

The template uses layered architecture:

```
┌─────────────────────────────────────────┐
│              HTTP Layer                 │  ← Controllers, Actions
├─────────────────────────────────────────┤
│           Application Layer             │  ← Commands, Queries, Handlers
├─────────────────────────────────────────┤
│             Domain Layer                │  ← Entities, Value Objects, Rules
├─────────────────────────────────────────┤
│          Infrastructure Layer           │  ← Repositories, External Services
└─────────────────────────────────────────┘
```

**Development order:** Domain → Infrastructure → Application → HTTP

## Quick Start: Your First Feature

1. **Create business overview** (if not exists)
   ```
   /docs/business/overview.md
   ```

2. **Create roadmap** (recommended for any project with multiple features)
   ```
   /docs/working_docs/roadmap.md
   ```
   Define implementation order, dependencies, and phases. Essential for detecting blockers early.

3. **Create epic folder**
   ```
   /docs/working_docs/epics/my-first-epic/
   ```

4. **Write or generate requirements**
   - Ask Agent 1 to write requirements, OR
   - Write draft and ask Agent 1 to complete

5. **Validate with Agent 2**

6. **Slice if needed with Agent 2.5** (optional, for large requirements)

7. **Design with Agent 3**

8. **Create tasks with Agent 4**

9. **Implement following architecture guides**

10. **Validate with Agents 5-10**

11. **Deploy and update documentation**

## Language Agnostic

This template is **programming language agnostic**. The architecture patterns and agent workflows apply to any language:
- TypeScript/JavaScript
- Python
- Go
- Java
- PHP
- Rust
- etc.

Adapt the code examples in `/ai_docs/architecture/` to your stack.

## Documentation Index

| Document | Purpose |
|----------|---------|
| `/ai_docs/architecture/architecture.md` | Architecture overview |
| `/ai_docs/architecture/critical-rules.md` | Must-follow rules |
| `/ai_docs/architecture/frontend-backend-integration.md` | **Full-stack development strategies** |
| `/ai_docs/agents/README.md` | Agent pipeline overview |
| `/docs/api-contracts/` | API contract definitions |
| `/docs/development/development_process.md` | Complete workflow guide |
| `/docs/business/overview.md` | Business context (MANDATORY) |

## Contributing

When contributing to this template:
1. Follow the existing architecture patterns
2. Update documentation when adding features
3. Test with multiple programming languages if possible

## License

MIT License
