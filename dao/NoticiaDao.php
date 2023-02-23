<?php

final class NoticiaDao {

    /** @var PDO */
    private $db = null;


    public function __destruct() {
        // close db connection
        $this->db = null;
    }

    /**
     * Find all {@link Noticia}s by search criteria.
     * @return array array of {@link Noticia}s
     */
    public function find(NoticiaSearchCriteria $search = null) {
        $result = array();
        foreach ($this->query($this->getFindSql($search)) as $row) {
            $noticia = new Noticia();
            NoticiaMapper::map($noticia, $row);
            $result[$noticia->getId()] = $noticia;
        }
        return $result;
    }

    /**
     * Find {@link Noticia} by identifier.
     * @return Noticia Noticia or <i>null</i> if not found
     */
    public function findById($id) {
        $row = $this->query('SELECT * FROM noticia WHERE deleted = 0 and id = ' . (int) $id)->fetch();
        if (!$row) {
            return null;
        }
        $noticia = new Noticia();
        NoticiaMapper::map($noticia, $row);
        return $noticia;
    }

    /**
     * Save {@link Noticia}.
     * @param Noticia $noticia {@link Noticia} to be saved
     * @return Noticia saved {@link Noticia} instance
     */
    public function save(Noticia $noticia) {
        if ($noticia->getId() === null) {
            return $this->insert($noticia);
        }
        return $this->update($noticia);
    }

    /**
     * Delete {@link Noticia} by identifier.
     * @param int $id {@link Noticia} identifier
     * @return bool <i>true</i> on success, <i>false</i> otherwise
     */
    public function delete($id) {
        $sql = '
            UPDATE noticia SET
                last_modified_on = :last_modified_on,
                deleted = :deleted
            WHERE
                id = :id';
        $statement = $this->getDb()->prepare($sql);
        $this->executeStatement($statement, array(
            ':last_modified_on' => self::formatDateTime(new DateTime()),
            ':deleted' => true,
            ':id' => $id,
        ));
        return $statement->rowCount() == 1;
    }

    /**
     * @return PDO
     */
    private function getDb() {
        if ($this->db !== null) {
            return $this->db;
        }
        $config = Config::getConfig("db");
        try {
            $this->db = new PDO($config['dsn'], $config['username'], $config['password']);
        } catch (Exception $ex) {
            throw new Exception('DB connection error: ' . $ex->getMessage());
        }
        return $this->db;
    }

    private function getFindSql(NoticiaSearchCriteria $search = null) {
        $sql = 'SELECT * FROM noticia WHERE deleted = 0 ';
        $orderBy = ' priority, due_on';
        if ($search !== null) {
            if ($search->getStatus() !== null) {
                $sql .= 'AND status = ' . $this->getDb()->quote($search->getStatus());
                switch ($search->getStatus()) {
                    case Noticia::STATUS_PENDING:
                        $orderBy = 'due_on, priority';
                        break;
                    case Noticia::STATUS_DONE:
                    case Noticia::STATUS_VOIDED:
                        $orderBy = 'due_on DESC, priority';
                        break;
                    default:
                        throw new Exception('No order for status: ' . $search->getStatus());
                }
            }
        }
        $sql .= ' ORDER BY ' . $orderBy;
        return $sql;
    }

    /**
     * @return Noticia
     * @throws Exception
     */
    private function insert(Noticia $noticia) {
        $now = new DateTime();
        $noticia->setId(null);
        $noticia->setCreatedOn($now);
        $noticia->setLastModifiedOn($now);
        $noticia->setStatus(Noticia::STATUS_PENDING);
        $sql = '
            INSERT INTO noticia (id, priority, created_on, last_modified_on, due_on, title, description, comment, status, deleted)
                VALUES (:id, :priority, :created_on, :last_modified_on, :due_on, :title, :description, :comment, :status, :deleted)';
        return $this->execute($sql, $noticia);
    }

    /**
     * @return Noticia
     * @throws Exception
     */
    private function update(Noticia $noticia) {
        $noticia->setLastModifiedOn(new DateTime());
        $sql = '
            UPDATE noticia SET
                priority = :priority,
                last_modified_on = :last_modified_on,
                due_on = :due_on,
                title = :title,
                description = :description,
                comment = :comment,
                status = :status,
                deleted = :deleted
            WHERE
                id = :id';
        return $this->execute($sql, $noticia);
    }

    /**
     * @return Noticia
     * @throws Exception
     */
    private function execute($sql, Noticia $noticia) {
        $statement = $this->getDb()->prepare($sql);
        $this->executeStatement($statement, $this->getParams($noticia));
        if (!$noticia->getId()) {
            return $this->findById($this->getDb()->lastInsertId());
        }
        if (!$statement->rowCount()) {
            throw new NotFoundException('NOTICIA with ID "' . $noticia->getId() . '" does not exist.');
        }
        return $noticia;
    }

    private function getParams(Noticia $noticia) {
        $params = array(
            ':id' => $noticia->getId(),
            ':priority' => $noticia->getPriority(),
            ':created_on' => self::formatDateTime($noticia->getCreatedOn()),
            ':last_modified_on' => self::formatDateTime($noticia->getLastModifiedOn()),
            ':due_on' => self::formatDateTime($noticia->getDueOn()),
            ':title' => $noticia->getTitle(),
            ':description' => $noticia->getDescription(),
            ':comment' => $noticia->getComment(),
            ':status' => $noticia->getStatus(),
            ':deleted' => $noticia->getDeleted(),
        );
        if ($noticia->getId()) {
            // unset created date, this one is never updated
            unset($params[':created_on']);
        }
        return $params;
    }

    private function executeStatement(PDOStatement $statement, array $params) {
        if (!$statement->execute($params)) {
            self::throwDbError($this->getDb()->errorInfo());
        }
    }

    /**
     * @return PDOStatement
     */
    private function query($sql) {
        $statement = $this->getDb()->query($sql, PDO::FETCH_ASSOC);
        if ($statement === false) {
            self::throwDbError($this->getDb()->errorInfo());
        }
        return $statement;
    }

    private static function throwDbError(array $errorInfo) {
        // NOTICIA log error, send email, etc.
        throw new Exception('DB error [' . $errorInfo[0] . ', ' . $errorInfo[1] . ']: ' . $errorInfo[2]);
    }

    private static function formatDateTime(DateTime $date) {
        return $date->format(DateTime::ISO8601);
    }

}
