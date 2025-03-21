import { afterEach, beforeEach, describe, expect, test } from 'vitest';
import { addElasticsearchServerInput, selectDatabaseSetup } from './setup';

describe('selectDatabaseSetup', () => {
  let database: HTMLElement;
  let databasePort: HTMLInputElement;
  let sqlite: HTMLElement;

  beforeEach(() => {
    document.body.innerHTML = `
      <form id="phpmyfaq-setup-form">
        <select id="databaseSelect">
          <option value="mysqli">MySQL</option>
          <option value="pgsql">PostgreSQL</option>
          <option value="sqlsrv">SQL Server</option>
          <option value="sqlite3">SQLite</option>
        </select>
        <input type="text" id="dbdatafull">
        <input type="text" id="sql_port">
        <div id="dbsqlite"></div>
      </form>
    `;
    database = document.getElementById('dbdatafull') as HTMLElement;
    databasePort = document.getElementById('sql_port') as HTMLInputElement;
    sqlite = document.getElementById('dbsqlite') as HTMLElement;
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should select MySQLi and update elements', () => {
    const event = { target: { value: 'mysqli' } } as unknown as Event;

    selectDatabaseSetup(event);

    expect(databasePort.value).toBe('3306');
    expect(sqlite.className).toBe('d-none');
    expect(database.className).toBe('d-block');
  });

  test('should select PostgreSQL and update elements', () => {
    const event = { target: { value: 'pgsql' } } as unknown as Event;

    selectDatabaseSetup(event);

    expect(databasePort.value).toBe('5432');
    expect(sqlite.className).toBe('d-none');
    expect(database.className).toBe('d-block');
  });

  test('should select SQL Server and update elements', () => {
    const event = { target: { value: 'sqlsrv' } } as unknown as Event;

    selectDatabaseSetup(event);

    expect(databasePort.value).toBe('1433');
    expect(sqlite.className).toBe('d-none');
    expect(database.className).toBe('d-block');
  });

  test('should select SQLite and update elements', () => {
    const event = { target: { value: 'sqlite3' } } as unknown as Event;

    selectDatabaseSetup(event);

    expect(sqlite.className).toBe('d-block');
    expect(database.className).toBe('d-none');
  });

  test('should select default option and update elements', () => {
    const event = { target: { value: 'unknown' } } as unknown as Event;

    selectDatabaseSetup(event);

    expect(sqlite.className).toBe('d-none');
    expect(database.className).toBe('d-block');
  });
});

describe('addElasticsearchServerInput', () => {
  let wrapper: HTMLElement;

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="elasticsearch-server-wrapper"></div>
    `;
    wrapper = document.getElementById('elasticsearch-server-wrapper') as HTMLElement;
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test('should add a new input element after the wrapper', () => {
    addElasticsearchServerInput();

    const input = wrapper.nextElementSibling as HTMLInputElement;
    expect(input.tagName).toBe('INPUT');
    expect(input.className).toBe('form-control mt-1');
    expect(input.type).toBe('text');
    expect(input.name).toBe('elasticsearch_server[]');
    expect(input.placeholder).toBe('127.0.0.1:9200');
  });
});
