<?php
function last_log_edit(mysqli $connection, string $row)
{
    $query = "CALL LastLogEdit(";
    if (!$row)
    {
        //Update every row
        $query .= "NULL";
    } else {
        $query .= "'" . $connection->real_escape_string($row) . "'";
    }
    $query .= ");";
    $result = (bool)$connection->query($query);
    $connection->next_result();
    return $result;
}
function last_log_get(mysqli $connection)
{
    $query = "SELECT * FROM LastLogGet";
    $result = $connection->query($query);
    if (!$result)
    {
        $connection->next_result();
        return "";
    }
    $data = "[\n";
    while($row = $result->fetch_assoc())
    {
        $area = $row["area"];
        $time_stamp = $row["time_stamp"];
        $url = $row["url"];

        $data .= 
            "  {\n" .
            "    \"area\": \"$area\",\n".
            "    \"timestamp\": \"$time_stamp\",\n".
            "    \"url\": \"$url\"\n".
            "  },\n";
    }
    $result->close();
    $connection->next_result();
    return $data . "]";
}
function getSystemStatus(mysqli $connection)
{
    $query = "CALL SelectSystemStatus()";
    $status = "Sconosciuto";
    $ret = $connection->query($query);
    if ($ret)
    {
        if ($row = $ret->fetch_assoc())
        {
            if (isset($row["status"]))
                $status = $row["status"];
        }
        $ret->close();
    }
    $connection->next_result();
    return $status;
}