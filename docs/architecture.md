# 13. phpMyFAQ Architecture & Class Diagrams

This document describes the architecture of the phpMyFAQ PHP backend
(`phpmyfaq/src/phpMyFAQ`) and visualizes the most important classes and their
relationships as [Mermaid](https://mermaid.js.org/) class diagrams.

Because the backend contains several hundred classes, a single flat diagram
would be unreadable. Instead the system is broken into its main subsystems, and
each gets a focused diagram showing the key classes, interfaces, inheritance,
and composition relationships. Helper, repository, and value-object classes are
included only where they clarify the design.

> **Notation:** `<|..` = *implements interface*, `<|--` = *extends class*,
> `-->` = *uses / holds a reference (composition)*, `..>` = *creates (factory)*.

## Table of Contents

1. [Subsystem Overview](#131-subsystem-overview)
2. [Request Lifecycle: Bootstrap, Kernel and Routing](#132-request-lifecycle-bootstrap-kernel-and-routing)
3. [Database Abstraction Layer](#133-database-abstraction-layer)
4. [Authentication, User and Permission](#134-authentication-user-and-permission)
5. [FAQ Domain Model](#135-faq-domain-model)
6. [Search Subsystem](#136-search-subsystem)
7. [Export and Mail Subsystems](#137-export-and-mail-subsystems)
8. [Cross-Cutting Patterns](#138-cross-cutting-patterns)

---

## 13.1 Subsystem Overview

phpMyFAQ is a request/response web application built on **Symfony HttpKernel**,
**Symfony Routing** (via PHP 8 `#[Route]` attributes), **Twig** templates, and a
home-grown database abstraction layer that supports MySQL, PostgreSQL, SQLite,
and SQL Server. Four entry points (`index.php`, `admin/index.php`,
`api/index.php`, `admin/api/index.php`) each boot a `Kernel` configured for a
different *routing context*.

```mermaid
flowchart TD
    subgraph Entry["Entry Points"]
        FE["index.php (public)"]
        ADM["admin/index.php (admin)"]
        API["api/index.php (api)"]
        AAPI["admin/api/index.php (admin-api)"]
    end

    subgraph HTTP["HTTP / Kernel Layer"]
        BOOT["Bootstrapper"]
        KERNEL["Kernel (HttpKernelInterface)"]
        ROUTING["Routing (attribute-based)"]
        LISTENERS["EventListeners (request/controller/exception)"]
        CTRL["Controllers (AbstractController)"]
    end

    subgraph Domain["Domain Services"]
        FAQ["Faq / Category / News / Comments / Tags"]
        SEARCH["Search"]
        EXPORT["Export"]
        USER["User / Auth / Permission"]
    end

    subgraph Infra["Infrastructure"]
        CONFIG["Configuration"]
        DB["DatabaseDriver"]
        TWIG["TwigWrapper"]
        MAIL["Mail"]
        CACHE["Cache / Session"]
    end

    Entry --> BOOT --> KERNEL
    KERNEL --> ROUTING
    KERNEL --> LISTENERS --> CTRL
    CTRL --> Domain
    Domain --> CONFIG
    CONFIG --> DB
    CTRL --> TWIG
    Domain --> MAIL
    Domain --> CACHE
```

The **`Configuration`** object is the backbone of the system: nearly every
domain service receives it via constructor injection, and it in turn exposes the
active `DatabaseDriver`, the plugin manager, the logger, and grouped settings
objects (mail, search, security, LDAP, layout, URL).

---

## 13.2 Request Lifecycle: Bootstrap, Kernel and Routing

A request is bootstrapped, routed against attribute-defined routes, dispatched
through Symfony event listeners, and resolved to a controller that is fetched —
fully dependency-injected — from the DI container.

```mermaid
classDiagram
    direction LR

    class Bootstrapper {
        +run() self
        +getFaqConfig() Configuration
        +getDb() DatabaseDriver
        +getRequest() Request
    }

    class Kernel {
        <<HttpKernelInterface>>
        +__construct(routingContext, debug)
        +boot() void
        +handle(Request) Response
        +getContainer() ContainerInterface
        +getRoutingContext() string
    }

    class Environment {
        +init()$ void
        +isDebugMode()$ bool
        +get(key, default)$ mixed
    }

    class Configuration {
        +get(item) mixed
        +set(key, value) bool
        +getAll() array
        +getDb() DatabaseDriver
        +getPluginManager() PluginManager
        +getConfigurationInstance()$ Configuration
    }

    class System {
        +getVersion()$ string
        +isUpdateNecessary(installedVersion)$ bool
        +checkRequiredExtensions() bool
    }

    class RouteCollectionBuilder {
        +build(context, attributesOnly) RouteCollection
    }
    class AttributeRouteLoader {
        +load(controllerDir, context) RouteCollection
    }
    class RouteCacheManager {
        +getRoutes(context, loader) RouteCollection
        +clear() void
    }

    class ContainerControllerResolver {
        +getController(Request) callable
    }
    class AbstractController {
        <<abstract>>
        +setContainer(ContainerInterface) void
        +render(file, context) Response
        +json(data, status) JsonResponse
        +userIsAuthenticated() void
        #userHasPermission(PermissionType) void
        #verifySessionCsrfToken(page, token) bool
    }

    class RouterListener {
        +onKernelRequest(RequestEvent) void
    }
    class LanguageListener {
        +onKernelRequest(RequestEvent) void
    }
    class ControllerContainerListener {
        +onKernelController(ControllerEvent) void
    }
    class ApiRateLimiterListener {
        +onKernelRequest(RequestEvent) void
    }
    class ApiExceptionListener {
        +onKernelException(ExceptionEvent) void
    }
    class WebExceptionListener {
        +onKernelException(ExceptionEvent) void
    }
    class SecurityHeadersListener {
        +onKernelResponse(ResponseEvent) void
    }

    Bootstrapper --> Configuration : builds
    Bootstrapper --> Environment : initializes
    Kernel --> RouteCollectionBuilder : loads routes
    Kernel --> RouteCacheManager : caches routes
    Kernel --> RouterListener : registers
    Kernel --> LanguageListener : registers
    Kernel --> ControllerContainerListener : registers
    Kernel --> ApiRateLimiterListener : registers (api)
    Kernel --> ApiExceptionListener : registers
    Kernel --> WebExceptionListener : registers
    Kernel --> SecurityHeadersListener : registers
    Kernel --> ContainerControllerResolver : resolves with
    RouteCollectionBuilder --> AttributeRouteLoader : delegates
    ContainerControllerResolver --> AbstractController : fetches from container
    ControllerContainerListener --> AbstractController : injects container
    AbstractController --> Configuration : uses
    System ..> Configuration : reads
```

**Flow:** `index.php` → `src/Bootstrap.php` (runs `Bootstrapper::run()`) →
`new Kernel(routingContext, debug)` → `Kernel::handle()`, which boots the kernel
on first use (`Kernel::boot()` builds or loads the cached container from
`services.php` and loads routes from cache or attributes) and dispatches the
Symfony kernel events:

1. `LanguageListener` (request, priority 300) initializes i18n.
2. `RouterListener` (request, priority 256) matches the URL to a route.
3. `ApiRateLimiterListener` (request, priority 150, `api` context only) enforces
   rate limits.
4. `ContainerControllerResolver` fetches the controller from the DI container,
   falling back to plain instantiation for unregistered controllers.
5. `ControllerContainerListener` (controller event) injects the shared
   container into the `AbstractController` and enforces authentication for
   every controller in the `Administration` namespace unless it implements
   `SkipsAuthenticationCheck`.
6. On error, `ApiExceptionListener` (priority 0, RFC 7807 JSON) or
   `WebExceptionListener` (priority -10, HTML error pages) produces the
   response.
7. `SecurityHeadersListener` (response, priority -100) adds CSP and hardening
   headers to HTML responses.

---

## 13.3 Database Abstraction Layer

All persistence goes through the `DatabaseDriver` interface. A static `Database`
factory instantiates the configured driver; native and PDO-based
implementations exist for every supported engine.

```mermaid
classDiagram
    direction TB

    class Database {
        <<factory>>
        +factory(type)$ DatabaseDriver
        +getInstance()$ DatabaseDriver
        +getType()$ string
        +setTablePrefix(prefix)$ void
    }

    class DatabaseDriver {
        <<interface>>
        +connect(host, user, password, db, port) bool
        +query(query, offset, rowcount) mixed
        +escape(string) string
        +fetchObject(result) mixed
        +queryPrepared(query, params) mixed
        +fetchAll(result) array
        +numRows(result) int
        +lastInsertId() int
    }

    class Mysqli
    class Pgsql
    class Sqlite3
    class Sqlsrv
    class PdoMysql
    class PdoPgsql
    class PdoSqlite
    class PdoSqlsrv

    class DatabaseHelper {
        <<readonly>>
        +alignTablePrefix(query, old, new)$ string
        +buildInsertQueries(query, table) array
    }

    DatabaseDriver <|.. Mysqli
    DatabaseDriver <|.. Pgsql
    DatabaseDriver <|.. Sqlite3
    DatabaseDriver <|.. Sqlsrv
    DatabaseDriver <|.. PdoMysql
    DatabaseDriver <|.. PdoPgsql
    DatabaseDriver <|.. PdoSqlite
    DatabaseDriver <|.. PdoSqlsrv

    Database ..> DatabaseDriver : creates
    DatabaseHelper --> Configuration : uses
    Configuration --> DatabaseDriver : exposes
```

> The native `Mysqli`, `Pgsql`, `Sqlite3`, and `Sqlsrv` drivers are deprecated in
> favor of the PDO variants and scheduled for removal in phpMyFAQ 5.0.

---

## 13.4 Authentication, User and Permission

Authentication uses the **strategy pattern**: `Auth::selectAuth()` returns a
driver that extends `Auth` and implements `AuthDriverInterface`. A `User`
composes one or more auth drivers plus a `PermissionInterface` strategy.
`CurrentUser` extends `User` with session/cookie handling, login throttling, and
2FA. `AuthWebAuthn` extends `Auth` for passkey logins but is not an
`AuthDriverInterface` driver. For API requests, `AuthChain` tries session → API
key → OAuth2 (an optional callable set via `setOAuth2Authenticator()`) in turn.

```mermaid
classDiagram
    direction TB

    class AuthDriverInterface {
        <<interface>>
        +create(login, password, domain) mixed
        +update(login, password) bool
        +delete(login) bool
        +checkCredentials(login, password, data) bool
        +isValidLogin(login, data) int
    }

    class Auth {
        +selectAuth(method) Auth
        +getEncryptionContainer(type) Encryption
        +encrypt(string) string
    }

    class AuthDatabase
    class AuthLdap
    class AuthSso
    class AuthKeycloak
    class AuthOidc
    class AuthEntraId
    class AuthHttp
    class AuthWebAuthn

    class PasswordHasher {
        <<final>>
        +hash(password) string
        +verify(login, password, hash) bool
        +needsRehash(hash) bool
    }

    class User {
        +addPerm(PermissionInterface) bool
        +addAuth(AuthDriverInterface, name) bool
        +createUser(login, pass, domain, userId) bool
        +getUserByLogin(login, raiseError) bool
        +changePassword(pass) bool
        +setStatus(status) bool
    }

    class CurrentUser {
        +login(login, password) bool
        +getFromSession(Configuration)$ CurrentUser
        +getFromCookie(Configuration)$ CurrentUser
        +deleteFromSession(deleteCookie) bool
        +isLoggedIn() bool
        +twoFactorSuccess() bool
    }

    class UserData {
        +get(field) mixed
        +set(field, value) bool
    }
    class UserSession {
        +checkSessionId(sessionId, ipAddress) void
        +userTracking(action, data) void
        +setCookie(name, sessionId, timeout, strict) void
    }

    class AuthChain {
        <<final>>
        +authenticate(request, scopes) bool
        +setOAuth2Authenticator(callable) void
        +getAuthenticatedUserId() int
    }
    class ApiKeyAuthenticator {
        <<final>>
        +authenticate(request, scopes) bool
        +getAuthenticatedUserId() int
    }

    class PermissionInterface {
        <<interface>>
        +hasPermission(userId, right) bool
        +grantUserRight(userId, rightId) bool
        +getRightData(rightId) array
    }
    class BasicPermission {
        +checkUserRight(userId, rightId) bool
        +refuseAllUserRights(userId) bool
        +getAllUserRights(userId) array
    }
    class MediumPermission {
        +grantGroupRight(groupId, rightId) bool
        +addToGroup(userId, groupId) bool
        +getGroupRights(groupId) array
    }

    AuthDriverInterface <|.. AuthDatabase
    AuthDriverInterface <|.. AuthLdap
    AuthDriverInterface <|.. AuthSso
    AuthDriverInterface <|.. AuthKeycloak
    AuthDriverInterface <|.. AuthOidc
    AuthDriverInterface <|.. AuthEntraId
    AuthDriverInterface <|.. AuthHttp
    Auth <|-- AuthDatabase
    Auth <|-- AuthLdap
    Auth <|-- AuthSso
    Auth <|-- AuthKeycloak
    Auth <|-- AuthOidc
    Auth <|-- AuthEntraId
    Auth <|-- AuthHttp
    Auth <|-- AuthWebAuthn

    User <|-- CurrentUser
    User --> PermissionInterface : composes
    User --> AuthDriverInterface : auth container
    User --> UserData : profile
    CurrentUser --> UserSession : session and cookies
    AuthDatabase --> PasswordHasher : uses

    PermissionInterface <|.. BasicPermission
    BasicPermission <|-- MediumPermission

    AuthChain --> ApiKeyAuthenticator : chains
    AuthChain --> CurrentUser : session auth
```

`PermissionInterface` has two implementations: `BasicPermission` (per-user
rights) and `MediumPermission` (adds group rights and group membership). Both are
backed by readonly repository classes (`BasicPermissionRepository`,
`MediumPermissionRepository`, `GroupCategoryPermissionRepository`).

---

## 13.5 FAQ Domain Model

The domain layer is **service-oriented**: each aggregate (`Faq`, `Category`,
`News`, …) is a service that operates on plain *entity* value objects from the
`Entity\` namespace and persists through dedicated repositories (`FaqRepository`,
`CategoryRepository`, `CommentsRepository`, `NewsRepository`, …). The services
are largely independent of each other; controllers such as the frontend
`FaqController` combine them to build a page.

```mermaid
classDiagram
    direction TB

    class Faq {
        +getFaq(faqId, revisionId, isAdmin) void
        +getAllAvailableFaqsByCategoryId(id, ...) array
        +create(FaqEntity) FaqEntity
        +update(FaqEntity) FaqEntity
        +delete(id, lang) bool
    }
    class Category {
        +getOrderedCategories(...) array
        +getCategoryData(id) CategoryEntity
        +buildCategoryTree(id, depth) void
        +getPath(id) array
    }
    class News {
        <<readonly>>
        +getAll(showArchive, active) array
        +create(NewsMessage) bool
        +update(NewsMessage) bool
    }
    class Comments {
        +getCommentsData(referenceId, type) array
        +create(Comment) bool
        +delete(type, id) bool
    }
    class Tags {
        +getAllTagsById(id) array
        +create(id, tags) bool
        +getPopularTags(limit) array
    }
    class Rating {
        <<readonly>>
        +get(id) string
        +create(Vote) bool
    }
    class Visits {
        <<readonly>>
        +logViews(id) void
        +add(id) bool
    }
    class Question {
        <<readonly>>
        +add(QuestionEntity) int
        +getAll(showAll, language) array
    }
    class Glossary {
        +insertItemsIntoContent(content) string
        +fetchAll() array
    }
    class Bookmark {
        +add(faqId) bool
        +getAll() array
        +remove(faqId) bool
    }

    class FaqEntity {
        <<entity>>
        -id : int
        -language : string
        -solutionId : int
        -status : FaqStatus
        -question : string
        -answer : string
    }
    class CategoryEntity {
        <<entity>>
        -id : int
        -parentId : int
        -name : string
    }
    class Comment {
        <<entity>>
        -id : int
        -type : string
        -comment : string
    }
    class NewsMessage {
        <<entity>>
    }
    class QuestionEntity {
        <<entity>>
    }
    class Vote {
        <<entity>>
    }
    class Tag {
        <<entity>>
    }

    class FaqRepository
    class FaqStatus {
        <<enum>>
    }
    class PermissionType {
        <<enum>>
    }
    class CommentType {
        <<enum>>
    }
    class SeoType {
        <<enum>>
    }

    Faq --> FaqEntity : reads/writes
    Faq --> FaqRepository : persists via
    Faq --> Category : category data
    FaqEntity --> FaqStatus : typed by
    Category --> CategoryEntity : reads/writes
    Comments --> Comment : reads/writes
    Comments --> CommentType : typed by
    News --> NewsMessage : reads/writes
    Question --> QuestionEntity : reads/writes
    Rating --> Vote : reads/writes
    Tags --> Tag : reads/writes
    Bookmark --> CurrentUser : owner

    Faq ..> Configuration : injected
    Category ..> Configuration : injected
    News ..> Configuration : injected
```

All services receive `Configuration` via the constructor (omitted from most
arrows above for clarity). Entities are fluent data containers with private
properties and getters/setters; enums such as `FaqStatus`, `PermissionType`,
`AdminLogType`, and `SeoType` (in `Enums\`) model fixed sets to keep illegal
states unrepresentable. `CommentType` lives in `Entity\` and holds string
constants (`CommentType::FAQ`, …).

---

## 13.6 Search Subsystem

Search follows the **strategy + factory** pattern. The high-level `Search`
service routes to a database, Elasticsearch, or OpenSearch backend depending on
configuration; all three extend `AbstractSearch` and implement `SearchInterface`.
`SearchResultSet` post-processes hits, applying permission filtering.

```mermaid
classDiagram
    direction TB

    class SearchService {
        <<final>>
        +processSearch(input, tag, category, allLang, page) array
        +shouldRedirectToSolutionId(input, num) bool
    }
    class Search {
        +search(term, allLanguages) array
        +autoComplete(term) array
        +logSearchTerm(term) void
        +getMostPopularSearches(num, withLang, window) array
    }

    class SearchInterface {
        <<interface>>
        +search(searchTerm) mixed
    }
    class AbstractSearch {
        <<abstract>>
        #resultSet
        #configuration
    }
    class SearchDatabase {
        +setTable(table) self
        +setMatchingColumns(cols) self
        +setConditions(conds) self
    }
    class Elasticsearch {
        +autoComplete(term) array
        +setCategoryIds(ids) void
    }
    class OpenSearch {
        +autoComplete(term) array
        +setCategoryIds(ids) void
    }

    class SearchFactory {
        +create(config, handler)$ SearchDatabase
    }
    class SearchResultSet {
        +reviewResultSet(set) void
        +getResultSet() array
        +getNumberOfResults() int
    }

    SearchInterface <|.. SearchDatabase
    SearchInterface <|.. Elasticsearch
    SearchInterface <|.. OpenSearch
    AbstractSearch <|-- SearchDatabase
    AbstractSearch <|-- Elasticsearch
    AbstractSearch <|-- OpenSearch

    SearchService --> Search : orchestrates
    SearchService --> SearchResultSet : reviews hits
    SearchService --> Faq : resolves
    SearchService --> Category : resolves
    Search --> SearchFactory : builds database backend
    Search ..> Elasticsearch : creates
    Search ..> OpenSearch : creates
    SearchFactory ..> SearchDatabase : creates
    SearchResultSet --> CurrentUser : permission filter
```

---

## 13.7 Export and Mail Subsystems

**Export** uses a static factory on the `Export` base class to produce a
`Pdf` or `Json` exporter. **Mail** separates two extension points: low-level
*user agents* (`MailUserAgentInterface`: SMTP, PHP `mail()`) and high-level
*providers* (`MailProviderInterface`: SendGrid, Mailgun, AWS SES).

```mermaid
classDiagram
    direction TB

    class Export {
        +create(faq, cat, config, mode)$ Export
        +getExportTimestamp()$ string
    }
    class Json {
        +generate(categoryId, downwards, lang) string
    }
    class Pdf {
        +generate(categoryId, downwards, lang) string
    }
    Export <|-- Json
    Export <|-- Pdf
    Export ..> Json : creates
    Export ..> Pdf : creates
    Pdf --> Wrapper : renders via

    class Wrapper {
        +Open() void
        +SetDisplayMode(zoom) void
        +Output(name, dest) string
    }
    class PdfEngineInterface {
        <<interface>>
    }
    class TcpdfEngine
    PdfEngineInterface <|.. TcpdfEngine
    Wrapper --> PdfEngineInterface : uses

    class Mail {
        +addTo(address, name) bool
        +send(forceSynchronousDelivery) int
    }
    class MailUserAgentInterface {
        <<interface>>
        +send(recipients, headers, body) int
    }
    class Smtp
    class Builtin
    class MailProviderInterface {
        <<interface>>
        +send(recipients, headers, body) int
    }
    class SendGridProvider
    class MailgunProvider
    class SesProvider

    MailUserAgentInterface <|.. Smtp
    MailUserAgentInterface <|.. Builtin
    MailProviderInterface <|.. SendGridProvider
    MailProviderInterface <|.. MailgunProvider
    MailProviderInterface <|.. SesProvider
    Mail --> MailUserAgentInterface : delegates
    Mail --> MailProviderInterface : delegates
```

The **Setup/Migration** subsystem (not diagrammed here) follows the same
philosophy: a `MigrationInterface` / `AbstractMigration` hierarchy records
`OperationInterface` steps (SQL, config, file, permission operations) into an
`OperationRecorder`, executed or dry-run by `MigrationExecutor` against a
database-specific `DialectInterface` produced by `DialectFactory`.

---

## 13.8 Cross-Cutting Patterns

The codebase consistently applies a small set of design patterns. Recognizing
them makes the rest of the system predictable:

| Pattern | Where | Purpose |
| --- | --- | --- |
| **Factory** | `Database::factory()`, `Auth::selectAuth()`, `Export::create()`, `SearchFactory`, `DialectFactory` | Pick a concrete implementation from configuration |
| **Strategy** | `DatabaseDriver`, `AuthDriverInterface`, `PermissionInterface`, `SearchInterface`, `MailUserAgentInterface` | Swap behavior behind a stable interface |
| **Repository** | `*Repository` classes per domain service | Isolate SQL from business logic |
| **Entity / Value Object** | `Entity\*` | Typed, fluent data containers |
| **Chain of Responsibility** | `AuthChain` (session → API key → OAuth2) | Try multiple authenticators in order |
| **Event Listener** | `*Listener` on Symfony kernel events | Cross-cutting request/exception handling |
| **Dependency Injection** | `services.php` + Symfony container | Constructor-wire all services and controllers |
| **Builder / Fluent** | `SearchDatabase`, `QueryBuilder`, entity setters | Stepwise, readable construction |
| **Enum for fixed sets** | `Enums\*` (`PermissionType`, `SeoType`, …) | Make illegal states unrepresentable |

### Where to look in the code

| Concern | Path |
| --- | --- |
| Bootstrap & kernel | `phpmyfaq/src/Bootstrap.php`, `phpmyfaq/src/phpMyFAQ/Bootstrapper.php`, `Kernel.php`, `Bootstrap/` |
| Event listeners | `phpmyfaq/src/phpMyFAQ/EventListener/` |
| Routing | `phpmyfaq/src/phpMyFAQ/Routing/`, controller `#[Route]` attributes |
| Controllers | `phpmyfaq/src/phpMyFAQ/Controller/` |
| DI configuration | `phpmyfaq/src/services.php` |
| Database | `phpmyfaq/src/phpMyFAQ/Database/` |
| Auth / User / Permission | `phpmyfaq/src/phpMyFAQ/Auth/`, `User/`, `Permission/` |
| Domain services | `phpmyfaq/src/phpMyFAQ/Faq.php`, `Category.php`, `News.php`, … |
| Entities & enums | `phpmyfaq/src/phpMyFAQ/Entity/`, `Enums/` |
| Search | `phpmyfaq/src/phpMyFAQ/Search.php`, `Search/` |
| Export / Mail | `phpmyfaq/src/phpMyFAQ/Export.php`, `Export/`, `Mail.php`, `Mail/` |
| Setup / migrations | `phpmyfaq/src/phpMyFAQ/Setup/Migration/` |
| Plugins | `phpmyfaq/src/phpMyFAQ/Plugin/`, `phpmyfaq/content/plugins/` |
| Templating | `phpmyfaq/src/phpMyFAQ/Twig/`, `Template/` |

---

*Generated from static analysis of the `phpmyfaq/src/phpMyFAQ` source tree.*
*Diagrams render automatically on GitHub and in any Mermaid-aware Markdown viewer.*
