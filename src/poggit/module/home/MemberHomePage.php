<?php

/*
 * Poggit
 *
 * Copyright (C) 2016 Poggit
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace poggit\module\home;

use poggit\module\VarPage;
use poggit\Poggit;
use poggit\session\SessionUtils;
use poggit\timeline\TimeLineEvent;

class MemberHomePage extends VarPage {
    /** @var array[] */
    private $timeline;
    /** @var array[] */
    private $projects;

    public function __construct() {
        $session = SessionUtils::getInstance();
        $repos = [];
        foreach(Poggit::ghApiGet("user/repos?per_page=75", $session->getAccessToken()) as $repo) {
            $repos[(int) $repo->id] = $repo;
        }
        $repoIdClause = implode(",", array_keys($repos));
        $this->timeline = Poggit::queryAndFetch("SELECT eventId, created, type, details 
            FROM user_timeline WHERE uid = ? ORDER BY created DESC LIMIT 50",
            "i", $session->getLogin()["uid"]);
        $this->projects = Poggit::queryAndFetch("SELECT r.repoId, p.projectId, p.name
            FROM projects p INNER JOIN repos r ON p.repoId = r.repoId 
            WHERE r.build = 1 AND p.projectId IN ($repoIdClause)");
    }

    public function bodyClasses() : array {
        return ["horiz-panes"];
    }

    public function getTitle() : string {
        return "Poggit";
    }

    public function output() {
        ?>
        <div class="horiz-pane">
            <h3>New Plugins</h3>
        </div>
        <div class="horiz-pane">
            <div class="timeline">
                <?php foreach($this->timeline as $event) { ?>
                    <div class="timeline-event">
                        <?php TimeLineEvent::fromJson((int) $event["type"], json_decode($event["details"]))->output() ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="horiz-pane">
            <h3>My projects</h3>
        </div>
        <?php
    }
}