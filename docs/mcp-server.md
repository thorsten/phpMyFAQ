# 12. phpMyFAQ MCP Server

This document describes the Model Context Protocol (MCP) server implementation for phpMyFAQ.

## 12.1 Overview

The phpMyFAQ MCP Server allows Large Language Models (LLMs) to query the phpMyFAQ installation through the Model
Context Protocol. This enables AI assistants to provide contextually relevant answers based on your FAQ.

The current implementation uses the official PHP package `mcp/sdk` and exposes the server over STDIO.

## 12.2 Usage

### 12.2.1 Starting the Server

```bash
# Show server information
php bin/console phpmyfaq:mcp:server --info

# Start the MCP server
php bin/console phpmyfaq:mcp:server
```

The command starts a STDIO MCP server. It is intended to be launched by an MCP-capable client or by the MCP Inspector.

### 12.2.2 Available Tools

#### faq_search

Search through the phpMyFAQ knowledge base to find relevant FAQ entries.

**Parameters:**
- `query` (required): The search query or question
- `category_id` (optional): Limit search to a specific category
- `limit` (optional): Maximum results to return (default: 10, max: 50)
- `all_languages` (optional): Search in all languages (default: false)

**Example Usage:**
```json
{
  "tool": "faq_search",
  "arguments": {
    "query": "How to reset password?",
    "limit": 5,
    "category_id": 1
  }
}
```

**Response Format:**
The tool returns JSON with:
- Question and answer content
- FAQ ID and language
- Relevance score
- Direct URL to the FAQ entry

Example result shape:

```json
{
  "results": [
    {
      "id": 42,
      "language": "en",
      "question": "How do I reset my password?",
      "answer": "Use the password reset form on the login page.",
      "category_id": 1,
      "relevance_score": 0.95,
      "url": "https://example.com/content/42/en"
    }
  ],
  "total_found": 1
}
```

## 12.3 Integration with LLM Clients

Once the MCP server is running, LLM clients can connect to it and use the `faq_search` tool to query your phpMyFAQ
database. The server follows the MCP specification and provides:

- Tool discovery via `tools/list`
- Tool execution via `tools/call`

### 12.3.1 Example with MCP Inspector

To test the server, you can use the MCP Inspector tool:

```bash
npx @modelcontextprotocol/inspector php bin/console phpmyfaq:mcp:server
```

You should see something like the following output:

```bash
Starting MCP inspector...
⚙️ Proxy server listening on localhost:6277
🔑 Session token: 428d7f0d505ea087fda1ef9005fbec76ade6cbdfd6f98a93c315b6207d4ac82a
   Use this token to authenticate requests or set DANGEROUSLY_OMIT_AUTH=true to disable auth

🚀 MCP Inspector is up and running at:
   http://localhost:6274/?MCP_PROXY_AUTH_TOKEN=428d7f0d505ea087fda1ef9005fbec76ade6cbdfd6f98a93c315b6207d4ac82a

🌐 Opening browser...
```

You can then access the MCP Inspector at the provided URL to interact with the server and test the `faq_search` tool.

## 12.4 Configuration

No additional MCP-specific configuration is required beyond having a working phpMyFAQ installation.

The MCP server uses the same phpMyFAQ runtime configuration as the rest of the application, including:

- database configuration
- language configuration
- URL configuration used for generated FAQ links
- the standard logger

## 12.4.1 Dependency Notes

The server runtime depends on:

- `mcp/sdk`
- `symfony/console`

If you update dependencies, make sure `mcp/sdk` remains installable with the project Composer constraints.

## 12.5 Error Handling

The server includes comprehensive error handling:
- Invalid search queries return descriptive error messages
- Database connection issues are logged and reported
- Malformed requests are handled gracefully

## 12.6 Security Considerations

- The MCP server only provides read access to publicly available FAQ content
- No authentication bypass or privileged access is provided
- Search results respect phpMyFAQ's existing visibility rules

## 12.7 Troubleshooting

### 12.7.1 Debugging

Enable debug logging by checking the Monolog output when running the server.

### 12.7.2 Runtime Issues

If the server does not start:

- verify Composer dependencies are installed
- verify `mcp/sdk` is present in the installed packages
- run `php bin/console phpmyfaq:mcp:server --info` to confirm the command is wired correctly
- test the server with MCP Inspector before connecting a custom client

### 12.7.3 Transport Notes

The phpMyFAQ MCP server currently uses STDIO only. It is not exposed as HTTP transport by default.
