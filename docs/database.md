# Database

## Production

### Setup

Docs: https://litestream.io/guides/systemd/

Short version:

1. Install as per: https://litestream.io/install/debian/

2. Create `/etc/livestream.yml`. Passing this info as arguments seems to fail. YML file method works.

    ```yml
    access-key-id:     <id>
    secret-access-key: <key> 

    dbs:
      - path: /var/www/5min-finance/database/database.sqlite
        replicas:
          - url: s3://5min-finance/db-backup
            sync-interval: 1s
    ```
   
    If changing config file:

    ```bash
    sudo systemctl restart litestream
    ```

3. Check logs: 
    ```bash
    sudo journalctl -u litestream -f
    ```

### Test

See: 

1. `sudo systemctl stop litestream`

2. Delete database files:
    ```bash
    rm -rf database/database.sqlite database/database.sqlite-shm database/database.sqlite-wal database/database.db-litestream
    ```

3. Visit 5min.finance to verify the database is down.  
    <br> 
    **⚠️ Note:** Visiting 5min.finance will create `database.sql` again, which needs to deleted before restoring.

### Restore

1. `sudo systemctl stop litestream`
 
2. Restore
    ```bash
    litestream restore database/database.sqlite 
   ```
   
3. `sudo systemctl start litestream`

4. Check logs:
    ```bash
    sudo journalctl -u litestream -f
    ```
