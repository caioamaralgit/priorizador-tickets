<?php

namespace App\Http\Controllers;

use App\Tickets;
use Illuminate\Http\Request;

class TicketsController extends Controller
{
    public function retrieveTickets(Request $request)
    {
        $variables = $this->validateVariables($request->order, $request->direction, $request->start_date, $request->end_date, 
            $request->priority, $request->limit);

        $whereConditions = $this->mountWhereClause($variables["start"], $variables["end"], $variables["priority"]);

        $tickets = Tickets::with("interactions")
            ->orderBy($variables["order"], $variables["direction"])
            ->where($whereConditions)
            ->paginate($variables["limit"]);

        return $tickets;
    }

    private function mountWhereClause($start, $end, $priority)
    {
        $whereConditions = [];

        if ($start !== null) 
        {
            array_push($whereConditions, ["created_at", ">=", $start]);
        }

        if ($end !== null) 
        {
            array_push($whereConditions, ["created_at", "<=", $end]);
        }

        if ($priority !== null) 
        {
            array_push($whereConditions, ["priority", "=", $priority]);
        }

        return $whereConditions;
    }

    private function validateVariables($order, $direction, $start, $end, $priority, $limit)
    {
        if ($order !== "created_at" && $order !== "updated_at" && $order !== "priority")
        {
            $order = "created_at";
        }

        $direction = strtolower($direction) !== "desc" ? "ASC" : "DESC";

        if ($end !== null)
        {
            $end .= " 23:59:59";
        }

        if ($start !== null && $end !== null) 
        {
            if (strtotime($start) > strtotime($end))
            {
                $end = null;
            }
        }

        $priority = strtolower($priority) !== "alta" ? (strtolower($priority) !== "normal" ? null : "Normal") : "Alta";

        $limit = (!is_numeric($limit) || $limit < 2) ? 2 : $limit;

        return [
            "direction" => $direction,
            "end" => $end,
            "limit" => $limit,
            "order" => $order,
            "priority" => $priority,
            "start" => $start
        ];
    }
}
