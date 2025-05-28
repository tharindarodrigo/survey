<?php

namespace Domain\Surveys\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResponseResource extends JsonResource
{

    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'survey_id' => $this->survey_id,
            'participant_email' => $this->participant_email,
            'response_text' => $this->response_text,
            'created_at' => $this->created_at,
        ];
    }
}
