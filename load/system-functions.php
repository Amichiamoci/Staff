<?php
function last_log_edit($connection, string $row)
{
    $query = "CALL LastLogEdit(";
    if (!$row)
    {
        //Update every row
        $query .= "NULL";
    } else {
        $query .= "'" . sql_sanitize($row) . "'";
    }
    $query .= ");";
    $result = (bool)mysqli_query($connection, $query);
    mysqli_next_result($connection);
    return $result;
}
function last_log_get($connection)
{
    $query = "SELECT * FROM LastLogGet";
    $result = mysqli_query($connection, $query);
    if (!$result)
    {
        mysqli_next_result($connection);
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
    mysqli_next_result($connection);
    return $data . "]";
}
function getSystemStatus($connection)
{
    $query = "CALL SelectSystemStatus()";
    $status = "Sconosciuto";
    $ret = mysqli_query($connection, $query);
    if ($ret)
    {
        if ($row = $ret->fetch_assoc())
        {
            if (isset($row["status"]))
                $status = $row["status"];
        }
        $ret->close();
    }
    mysqli_next_result($connection);
    return $status;
}