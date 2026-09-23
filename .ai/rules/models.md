---
paths:
    - 'app/Models/*.php'
---

# Models

## Use the Scope attribute for local scopes

Reusable query constraints are protected methods marked with the Scope attribute, taking Builder as the first parameter and dropping the scope prefix. Do not use public scopeXxx methods.

## Use the Attribute class for accessors and mutators

Accessors and mutators are protected methods returning Attribute::make with get and set. Do not use getXxxAttribute or setXxxAttribute.
