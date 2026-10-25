# 15. phpMyFAQ MCP Server

This document describes the Model Context Protocol (MCP) server implementation for phpMyFAQ.

## 15.1 Overview

The phpMyFAQ MCP Server allows Large Language Models (LLMs) to query the phpMyFAQ installation through the Model
Context Protocol. This enables AI assistants to provide contextually relevant answers based on your FAQ.

The implementation uses the official PHP package `mcp/sdk` and exposes the server over STDIO only. There is no HTTP
transport.

Note:
The MCP server is experimental. Its tools and command-line options might change in future releases.

## 15.2 Usage

### 15.2.1 Starting the Server

```bash
# Show server information (name, version, capabilities, available tools)
php bin/console phpmyfaq:mcp:server --info

# Start the MCP server
php bin/console phpmyfaq:mcp:server
```

`-i` is the short form of `--info`.

The command is only registered once phpMyFAQ is installed, because the server needs the database configuration. On an
installation without a database configuration, `bin/console` reports the command as not defined.

The command starts a STDIO MCP server. It is not meant to be run interactively: an MCP-capable client or the MCP
Inspector launches it as a subprocess and talks to it via standard input and output. Standard output only carries the
MCP protocol messages; the startup banner and error messages are written to standard error.

### 15.2.2 Available Tools

#### faq_search

Search through the phpMyFAQ knowledge base to find relevant FAQ entries. Both questions and answers are searched.

**Parameters:**
- `query` (string, required): The search query or question
- `category_id` (integer, optional, minimum 1): Limit the search to a specific category
- `limit` (integer, optional): Maximum number of results to return (default: 10, minimum: 1, maximum: 50)
- `all_languages` (boolean, optional): Search in all languages instead of the current language only (default: false)

**Example `tools/call` parameters:**
```json
{
  "name": "faq_search",
  "arguments": {
    "query": "How to reset password?",
    "limit": 5,
    "category_id": 1
  }
}
```

**Response Format:**
The tool returns a JSON object with these fields:

- `results`: list of FAQ entries, each with ID, language, question, answer, category ID, relevance score, and the
  direct URL to the FAQ entry
- `total_found`: number of entries in `results`, i.e., after `limit` has been applied

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

If nothing matches, the tool returns `{"results": [], "total_found": 0}`.

## 15.3 Integration with LLM Clients

Configure your MCP client to launch `php bin/console phpmyfaq:mcp:server` from the phpMyFAQ root directory. The
client can then use the `faq_search` tool to query your phpMyFAQ database. The server follows the MCP specification and
provides:

- Tool discovery via `tools/list`
- Tool execution via `tools/call`

### 15.3.1 Example with MCP Inspector

To test the server, you can use the MCP Inspector tool:

```bash
npx @modelcontextprotocol/inspector php bin/console phpmyfaq:mcp:server
```

The Inspector starts a local proxy, prints a session token, and opens a browser window with a URL like
`http://localhost:6274/?MCP_PROXY_AUTH_TOKEN=<token>`. Use it to list the tools and call `faq_search`.

## 15.4 Configuration

No additional MCP-specific configuration is required beyond having a working phpMyFAQ installation.

The MCP server uses the same phpMyFAQ runtime configuration as the rest of the application, including:

- database configuration
- language configuration (`main.language`, and `main.languageDetection` if enabled)
- URL configuration used for generated FAQ links

### 15.4.1 Dependency Notes

The server runtime depends on:

- `mcp/sdk` (Composer constraint `~0.9.0`)
- `symfony/console`

`mcp/sdk` is still a 0.x release, so a new minor version can contain breaking changes. All calls into the SDK are
isolated in `phpMyFAQ\Service\McpServer\McpSdkRuntime`; check that class first when updating the SDK.

## 15.5 Error Handling

Errors during a tool call are returned as text content of the tool result, not as protocol errors:

- An empty `query` returns `Error: Search query cannot be empty.`
- Exceptions during the search, e.g., database errors, return `Error searching FAQ database: <message>`

If the server cannot start, the command prints `Failed to start MCP server: <message>` to standard error and exits with
a non-zero exit code.

## 15.6 Security Considerations

- The MCP server only provides read access to FAQ content
- Searches run as an anonymous user, so only FAQs and categories visible to anonymous visitors are returned
- No authentication bypass or privileged access is provided

## 15.7 Troubleshooting

### 15.7.1 Debugging

The MCP server does not write its own log output. Use the MCP Inspector to inspect the requests and responses, and
check the tool result text for the error messages listed in section 15.5.

### 15.7.2 Runtime Issues

If the server does not start:

- verify phpMyFAQ is installed, otherwise the command is not available
- verify Composer dependencies are installed
- verify `mcp/sdk` is present in the installed packages
- run `php bin/console phpmyfaq:mcp:server --info` to confirm the command is wired correctly
- test the server with MCP Inspector before connecting a custom client
