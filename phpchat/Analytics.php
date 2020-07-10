<?php

require 'Database.php';

class Analytics
{
    protected $filename;
    protected $data = [];
    protected $database;
    protected $initial;
    protected $totalData = 0;

    public function __construct($filename, $initial = 0)
    {
        $this->filename = $filename;
        $this->_extractData();
        $this->initial = $initial;
    }

    private function _extractData()
    {
        $filename = $this->filename;
        $data = fopen($filename, 'r');
        while ($perLine = fgets($data)) {
            if ('' === trim($perLine)) {
                continue;
            } else {
                $text = trim($perLine);
                $timestamp = substr($text, 0, strpos($text, 'M - '));
                if ('' === trim($timestamp)) {
                    continue;
                } else {
                    if ('' !== trim(substr($text, 0, strpos($text, ' left'))) || '' !== trim(substr($text, 0, strpos($text, ' changed to '))) || '' !== trim(substr($text, 0, strpos($text, ' added '))) || '' !== trim(substr($text, 0, strpos($text, ' You were added'))) || '' !== trim(substr($text, 0, strpos($text, ' created group')))) {
                        continue;
                    } else {
                        $datetime = explode(', ', $timestamp);
                        $this->data['date'][] = $datetime[0];
                        $this->data['time'][] = $datetime[1] . 'M';
                        $this->data['message'][] = trim(substr($text, strpos($text, ': ') + 1));
                        $this->data['contact'][] = trim(substr(trim(substr($text, 0, strpos($text, ': '))), strpos(trim(substr($text, 0, strpos($text, ': '))), ' - ') + 2));
                        $this->totalData += 1;
                    }
                }
            }
        }
        fclose($data);
        $connection = new Database();
        $this->database = $connection->getDatabase();
        if ($this->initial === 0) {
            $this->_insertData();
        }
    }

    public function getData($type, $max = null)
    {
        $data = $this->data;
        if ($max == null) {
            for ($index = 0; $index < count($data); $index++) {
                echo $data[$type][$index] . "\n";
            }
        } else {
            for ($index = 0; $index < $max; $index++) {
                echo $data[$type][$index] . "\n";
            }
        }
    }

    private function _insertData()
    {
        $lists = $this->data;
        for ($index = 0; $index < $this->totalData; $index++) {
            $date = $lists['date'][$index];
            $datetime = date('Y-m-d', strtotime("$date"));
            $time = $lists['time'][$index];
            $message = str_replace("'", '', $lists['message'][$index]);
            $contact = $lists['contact'][$index];
            $links = $this->_countLinks($message);
            $letter_count = strlen($message);
            $word_count = str_word_count($message);
            $insertData = $this->database->exec("INSERT INTO datachat (date, time, contact, message, emoji, url, letter_count, word_count) VALUES ('$datetime', '$time', '$contact', '$message', 0, $links, $letter_count, $word_count)");
        }
    }

    private function _countLinks($string)
    {
        $pattern = '~[a-z]+://\S+~';
        if ($total = preg_match_all($pattern, $string, $out)) {
            return $total;
        } else {
            return 0;
        }
    }
}