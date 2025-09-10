# Multi-Agent Examples

This directory contains examples demonstrating multi-agent collaboration patterns using the Symfony AI framework. These examples showcase how specialized agents can work together to complete complex tasks through orchestrated workflows and direct collaboration.

## Overview

The multi-agent system implements two primary execution patterns discussed in [symfony/ai#19](https://github.com/symfony/ai/issues/19):

1. **Orchestrated Workflow**: A central orchestrator agent coordinates and delegates tasks to specialized agents
2. **Direct Collaboration**: Agents work directly with each other through context handoffs and capability detection

## Examples

### 1. `orchestrator.php` - Orchestrated Workflow Pattern

Demonstrates a central orchestrator that coordinates specialized agents as tools:

- **Orchestrator Agent**: Manages workflow and delegates tasks
- **Research Agent**: Gathers comprehensive information (low temperature for accuracy)
- **Writing Agent**: Creates engaging content (higher temperature for creativity)
- **Review Agent**: Provides quality assurance (low temperature for consistency)

**Key Features:**
- Central coordination through orchestrator
- Agents used as tools via `AgentTool`
- Clear workflow phases (research → write → review)
- Comprehensive task completion

### 2. `collaboration.php` - Direct Collaboration Pattern

Shows agents working directly together through handoffs:

- **Capability Detection**: Automatic agent selection based on task keywords
- **Context Handoffs**: Agents pass responsibility using `HANDOFF_TO_*` patterns
- **Autonomous Collaboration**: No central orchestrator needed
- **Iterative Processing**: Multi-step workflows with context preservation

**Key Features:**
- Dynamic agent detection
- Explicit handoff mechanisms
- Context preservation across agents
- Self-organizing workflows

### 3. `SpecializedAgents.php` - Reusable Agent Classes

Provides specialized agent classes with distinct capabilities:

- **ResearchAgent**: Information gathering and fact-finding
- **WritingAgent**: Content creation and storytelling  
- **ReviewAgent**: Quality assurance and feedback
- **AnalysisAgent**: Data analysis and insights
- **MultiAgentSystem**: System manager for agent coordination

**Key Features:**
- Object-oriented agent design
- Capability-based task matching
- Execution history tracking
- Standardized agent interfaces

### 4. `usage-example.php` - Comprehensive Usage Patterns

Demonstrates practical multi-agent system usage:

- **Sequential Workflows**: Step-by-step task completion
- **Automatic Agent Selection**: Dynamic task-to-agent matching
- **Complex Analysis**: Multi-phase analytical workflows
- **Iterative Improvement**: Feedback-driven enhancement cycles
- **Parallel Processing**: Concurrent task execution concepts

## Getting Started

### Prerequisites

1. Install dependencies:
   ```bash
   cd examples
   composer install
   ```

2. Configure environment variables:
   ```bash
   cp .env .env.local
   # Edit .env.local with your OpenAI API key
   ```

### Running Examples

Execute any example directly:

```bash
# Orchestrated workflow
php multi-agent/orchestrator.php

# Direct collaboration  
php multi-agent/collaboration.php

# Comprehensive usage patterns
php multi-agent/usage-example.php
```

### Verbose Output

Add verbosity flags for detailed logging:

```bash
php multi-agent/orchestrator.php -vvv
```

## Architecture Patterns

### Orchestrated Workflow

```
┌─────────────────┐
│   Orchestrator  │
│     Agent       │
└─────────┬───────┘
          │
    ┌─────┴─────┐
    │   Tools   │
    │           │
┌───▼───┐ ┌─────▼─┐ ┌──▼───┐
│Research│ │Writing│ │Review│
│ Agent  │ │ Agent │ │Agent │
└────────┘ └───────┘ └──────┘
```

### Direct Collaboration

```
┌─────────┐  handoff   ┌─────────┐  handoff   ┌─────────┐
│Research │──────────▶ │ Writing │──────────▶ │ Review  │
│ Agent   │            │  Agent  │            │  Agent  │
└─────────┘            └─────────┘            └─────────┘
     ▲                                              │
     │              revision needed                 │
     └──────────────────────────────────────────────┘
```

## Agent Specializations

### Research Agent
- **Temperature**: 0.1 (low for factual accuracy)
- **Capabilities**: Information gathering, fact-checking, data analysis
- **Output**: Structured research findings with sources

### Writing Agent  
- **Temperature**: 0.7 (higher for creativity)
- **Capabilities**: Content creation, storytelling, audience adaptation
- **Output**: Engaging, well-structured content

### Review Agent
- **Temperature**: 0.2 (low for consistent evaluation)
- **Capabilities**: Quality assurance, editing, feedback provision
- **Output**: Constructive feedback and improvement suggestions

### Analysis Agent
- **Temperature**: 0.3 (moderate for balanced analysis)
- **Capabilities**: Data interpretation, pattern recognition, insights
- **Output**: Evidence-based conclusions and recommendations

## Key Implementation Details

### Handoff Mechanisms

Agents detect handoffs through:
- **Explicit mentions**: `HANDOFF_TO_WRITING: content`
- **Capability detection**: Keyword matching against agent capabilities  
- **Pre-configured rules**: Defined workflow patterns

### Context Preservation

- Task context passes between agents
- Previous agent outputs included in subsequent prompts
- Execution history maintained for audit trails

### Error Handling

- Graceful fallbacks to default agents
- Maximum iteration limits prevent infinite loops
- Clear error messages for debugging

## Best Practices

1. **Task Design**: Structure tasks to clearly indicate required capabilities
2. **Context Management**: Keep context concise but comprehensive
3. **Agent Selection**: Use capability matching for optimal agent assignment
4. **Iteration Limits**: Set reasonable bounds for collaborative workflows
5. **Temperature Tuning**: Adjust temperatures based on agent specialization

## Extension Points

The multi-agent system can be extended with:

- **Custom Agents**: Implement `SpecializedAgent` base class
- **New Capabilities**: Add keywords and detection patterns
- **Additional Tools**: Integrate external services and APIs
- **Workflow Patterns**: Create domain-specific collaboration flows
- **Platform Support**: Extend beyond OpenAI to other AI platforms

## Performance Considerations

- **Sequential Processing**: Current examples process sequentially for clarity
- **Parallel Potential**: System design supports parallel execution
- **Cost Management**: Monitor token usage across multiple agents
- **Caching**: Consider result caching for repeated operations

## Contributing

When adding new multi-agent examples:

1. Follow existing naming conventions
2. Include comprehensive documentation
3. Demonstrate clear use cases
4. Add error handling and validation
5. Consider both orchestrated and collaborative patterns