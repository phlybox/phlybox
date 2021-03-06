<?php

namespace pascaldevink\Phlybox\Service\Storage;

use pascaldevink\Phlybox\Service\BoxStatus;

class SqliteStorageService implements MetaStorageService
{
    private static $databaseHandle = null;

    /**
     * Adds a box to the meta storage and returns the identifier.
     *
     * @param string $name
     * @param string $repositoryOwner
     * @param string $repositoryName
     * @param string $branch
     * @param int $prNumber
     *
     * @throws \Exception
     * @return int
     */
    public function addBox($name, $repositoryOwner, $repositoryName, $branch, $prNumber)
    {
        $baseStatus = BoxStatus::STATUS_CLONING;

        $databaseHandle = $this->getDatabaseHandle();
        $result = $databaseHandle->exec("INSERT INTO box (boxName, repositoryOwner, repositoryName, branch, prNumber, status) VALUES ('$name', '$repositoryOwner', '$repositoryName', '$branch', $prNumber, $baseStatus)");

        if ($result === false) {
            throw new \Exception('Something went wrong whiles saving the meta data');
        }

        $id = $databaseHandle->lastInsertRowID();
        return $id;
    }

    /**
     * Sets the status of the box with the given id.
     * Status can be any of the constants in this interface.
     *
     * @param int $boxId
     * @param int $status
     *
     * @return void
     */
    public function setBoxStatus($boxId, $status)
    {
        $databaseHandle = $this->getDatabaseHandle();
        $databaseHandle->exec("UPDATE box SET status = $status WHERE rowid = $boxId");
    }

    /**
     * Removes the box from the meta storage.
     *
     * @param int $boxId
     *
     * @return void
     */
    public function removeBox($boxId)
    {
        // TODO: Implement removeBox() method.
    }

    /**
     * Returns a list of all boxes.
     *
     * @return array
     */
    public function getAllBoxes()
    {
        $databaseHandler = $this->getDatabaseHandle();

        $statement = $databaseHandler->prepare("SELECT rowid, boxName, repositoryOwner, repositoryName, branch, prNumber, status FROM box");
        $result = $statement->execute();

        $resultArray = array();

        while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
            $box = array(
                'id'                => $res['rowid'],
                'boxName'           => $res['boxName'],
                'repositoryOwner'   => $res['repositoryOwner'],
                'repositoryName'    => $res['repositoryName'],
                'branch'            => $res['branch'],
                'prNumber'          => $res['prNumber'],
                'status'            => $res['status'],
            );

            $resultArray[] = $box;
        }

        return $resultArray;
    }

    /**
     * Returns the box with the given identifier.
     * If the box does not exist, an exception is thrown.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getBoxByIdentifier($id)
    {
        $databaseHandler = $this->getDatabaseHandle();

        $statement = $databaseHandler->prepare("SELECT rowid, boxName, repositoryOwner, repositoryName, branch, prNumber, status FROM box WHERE rowid = :id");
        $statement->bindParam(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();

        $resultBox = $result->fetchArray(SQLITE3_ASSOC);

        if ($resultBox === false) {
            throw new \Exception("Box with identifier '$id' not found");
        }

        return $resultBox;
    }

    /**
     * Creates and returns a database handle to use.
     *
     * @return \SQLite3
     */
    protected function getDatabaseHandle()
    {
        if (self::$databaseHandle !== null) {
            return self::$databaseHandle;
        }

        $filename = 'phlybox.db';
        $sqlExists = false;

        if (file_exists($filename)) {
            $sqlExists = true;
        }

        $sqlLite = new \SQLite3($filename);

        if (! $sqlExists) {
            $sqlLite->exec('CREATE TABLE box (boxName STRING, repositoryOwner STRING, repositoryName STRING, branch STRING, prNumber INT, status INT)');
        }

        self::$databaseHandle = $sqlLite;
        return self::$databaseHandle;
    }
}