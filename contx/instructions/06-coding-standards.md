# Coding Standards and Architecture Guidelines

## Automatic Standards Detection

When working on any project, AI agents MUST check for and follow project-specific standards in the following locations (in order of precedence):

1. `.contx/standards/` directory
2. `.augment/standards/` directory  
3. `.gemini/standards/` directory
4. `STANDARDS.md` or `ARCHITECTURE.md` in project root
5. `docs/standards/` directory
6. `.github/CONTRIBUTING.md` (for open source projects)

## Standards Hierarchy

Standards should be applied in the following precedence order:
1. **Project-specific standards** (found in locations above)
2. **Framework standards** (e.g., Laravel conventions, React best practices)
3. **Language standards** (e.g., PSR-12 for PHP, PEP-8 for Python)
4. **General best practices**

## Required Behaviors

### On Project Initialization
- Scan for standards files in the locations listed above
- Load and parse all found standards
- Acknowledge which standards are being followed in the first response
- Create a `.contx/standards/` directory if none exists and suggest creating standards

### When Writing Code
- Apply ALL relevant standards from loaded files
- Maintain consistency with existing code patterns
- Follow the project's established architecture
- Use the project's naming conventions
- Implement proper error handling as defined
- Add documentation per project standards

### When Reviewing Code
- Check compliance with loaded standards
- Suggest improvements based on standards
- Point out deviations from established patterns
- Recommend refactoring when appropriate

## Standard Categories to Check

### 1. Code Style
- Indentation and formatting
- Naming conventions (variables, functions, classes, files)
- Comment style and documentation
- Line length and file organization

### 2. Architecture Patterns  
- Design patterns in use (MVC, Repository, Service Layer, etc.)
- Directory structure
- Dependency injection approach
- Separation of concerns

### 3. Database Standards
- Migration patterns
- Schema design rules
- Query optimization requirements
- ORM usage guidelines

### 4. API Design
- Endpoint naming conventions
- Request/response formats
- Authentication methods
- Versioning strategy

### 5. Security Requirements
- Input validation rules
- Output escaping
- Authentication/authorization patterns
- Sensitive data handling

### 6. Testing Standards
- Test coverage requirements
- Testing patterns (unit, integration, e2e)
- Mock/stub usage
- Test naming conventions

### 7. Documentation Requirements
- Code comment standards
- README structure
- API documentation format
- Changelog maintenance

### 8. Git Workflow
- Branch naming conventions
- Commit message format
- Pull request templates
- Code review process

## Example Standards Check

When starting work on a task, agents should respond with something like:

```
I've detected the following project standards:
- ✅ Laravel coding standards in .contx/standards/laravel.md
- ✅ API design patterns in .contx/standards/api.md
- ✅ Testing requirements in .contx/standards/testing.md

I will follow these standards while implementing your request:
- Using service layer pattern for business logic
- Following PSR-12 coding style
- Implementing repository pattern for complex queries
- Adding PHPDoc blocks to all public methods
- Writing unit tests with >80% coverage
```

## Handling Conflicts

If standards conflict or are unclear:
1. Ask for clarification
2. Prefer project-specific over general standards
3. Maintain consistency with existing code
4. Document the decision made

## Creating Standards

If no standards exist, agents should:
1. Analyze existing code patterns
2. Suggest creating a standards document
3. Provide a template based on detected patterns
4. Recommend industry best practices

## Continuous Improvement

Agents should:
- Suggest updates to standards when patterns evolve
- Identify inconsistencies in the codebase
- Recommend refactoring to meet standards
- Help maintain standards documentation

## Integration with Task Workflow

Standards checking should be integrated into the task workflow:

1. **Problem Definition Phase**: Note any standards that affect the solution
2. **Research Phase**: Check how standards impact technical decisions  
3. **Planning Phase**: Include standards compliance in the plan
4. **Implementation Phase**: Apply all relevant standards
5. **Review Phase**: Verify standards compliance

## Reporting Non-Compliance

When encountering code that doesn't meet standards:
1. Note the deviation
2. Assess the impact
3. Suggest corrections
4. Provide refactoring steps if requested

## Standards File Format

Standards files can be in:
- Markdown (.md)
- YAML (.yml, .yaml)
- JSON (.json)
- Text (.txt)

Agents should be able to parse and understand all common formats.

---

**Remember**: Following established standards ensures code quality, maintainability, and consistency across the entire project. Always prioritize project-specific standards over general best practices.
