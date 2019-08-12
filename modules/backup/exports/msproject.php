<?php
global $id;
$id = 0;

function dumpTask($task)
{
	global $id;

	$output = '
		<Task>
			<UID>' . $task['task_id'] . '</UID>
			<ID>' . (++$id) . '</ID>
			<Name>' . $task['task_name'] . '</Name>
			<Type>0</Type>
			<IsNull>0</IsNull>
			<CreateDate>' . date('Y-m-d\TH:i:s') . '</CreateDate>
			<WBS>1</WBS>
			<OutlineNumber>1</OutlineNumber>
			<OutlineLevel>1</OutlineLevel>
			<Priority>' . $task['priority'] . '</Priority>
			<Start>' . $task['task_start_date'] . '</Start>
			<Finish>' . $task['task_end_date'] . '</Finish>
			<Duration>' . $task['task_duration'] . '</Duration>
			<DurationFormat>' . $task['task_duration_type'] . '</DurationFormat>
			<Work>' //calculate work from task logs?
			. ' </Work>
			<Stop>2005-05-30T17:00:00</Stop>
			<Resume>2005-05-31T08:00:00</Resume>
			<ResumeValid>0</ResumeValid>
			<EffortDriven>1</EffortDriven>
			<Recurring>0</Recurring>
			<OverAllocated>0</OverAllocated>
			<Estimated>0</Estimated>
			<Milestone>' . $task['task_milestone'] . '</Milestone>
			<Summary>0</Summary>
			<Critical>0</Critical>
			<IsSubproject>0</IsSubproject>
			<IsSubprojectReadOnly>0</IsSubprojectReadOnly>
			<ExternalTask>0</ExternalTask>
			<EarlyStart>2005-05-30T08:00:00</EarlyStart>
			<EarlyFinish>2005-05-31T17:00:00</EarlyFinish>
			<LateStart>2005-05-30T08:00:00</LateStart>
			<LateFinish>2005-06-01T17:00:00</LateFinish>
			<StartVariance>0</StartVariance>
			<FinishVariance>0</FinishVariance>
			<WorkVariance>1920000</WorkVariance>
			<FreeSlack>0</FreeSlack>
			<TotalSlack>4800</TotalSlack>
			<FixedCost>0</FixedCost>
			<FixedCostAccrual>3</FixedCostAccrual>
			<PercentComplete>' . $task['task_percent_complete'] . '</PercentComplete>
			<PercentWorkComplete>' . $task['task_percent_complete'] . '</PercentWorkComplete>
			<Cost>' . $task['task_target_budget'] . '</Cost>
			<OvertimeCost>0</OvertimeCost>
			<OvertimeWork>PT0H0M0S</OvertimeWork>
			<ActualStart>2005-05-30T08:00:00</ActualStart>
			<ActualDuration>PT8H0M0S</ActualDuration>
			<ActualCost>0</ActualCost>
			<ActualOvertimeCost>0</ActualOvertimeCost>
			<ActualWork>PT16H0M0S</ActualWork>
			<ActualOvertimeWork>PT0H0M0S</ActualOvertimeWork>
			<RegularWork>PT32H0M0S</RegularWork>
			<RemainingDuration>PT8H0M0S</RemainingDuration>
			<RemainingCost>0</RemainingCost>
			<RemainingWork>PT16H0M0S</RemainingWork>
			<RemainingOvertimeCost>0</RemainingOvertimeCost>
			<RemainingOvertimeWork>PT0H0M0S</RemainingOvertimeWork>
			<ACWP>0</ACWP>
			<CV>0</CV>
			<ConstraintType>0</ConstraintType>
			<CalendarUID>-1</CalendarUID>
			<LevelAssignments>1</LevelAssignments>
			<LevelingCanSplit>1</LevelingCanSplit>
			<LevelingDelay>0</LevelingDelay>
			<LevelingDelayFormat>8</LevelingDelayFormat>
			<IgnoreResourceCalendar>0</IgnoreResourceCalendar>
			<HideBar>0</HideBar>
			<Rollup>0</Rollup>
			<BCWS>0</BCWS>
			<BCWP>0</BCWP>
			<PhysicalPercentComplete>0</PhysicalPercentComplete>
			<EarnedValueMethod>0</EarnedValueMethod>
			<TimephasedData>
				<Type>11</Type>
				<UID>2</UID>
				<Start>2005-05-30T08:00:00</Start>
				<Finish>2005-05-30T17:00:00</Finish>
				<Unit>2</Unit>
				<Value>50</Value>
			</TimephasedData>
		</Task>';
	
	return $output;
}

function dumpProject($project_id = -1)
{
	if ($project_id == -1)
		return '';
	
	$q = new DBQuery();
	$q->addQuery('project_name');
	$q->addQuery('project_start_date, project_end_date');
  $q->addQuery('CONCAT(contact_first_name, \' \', contact_last_name) as owner');
	$q->addQuery('company_name');

	$q->addTable('projects');
	$q->leftJoin('companies', 'c', 'project_company = company_id');
	$q->leftJoin('users', 'u', 'project_owner = user_id');
	$q->leftJoin('contacts', 'co', 'user_contact = contact_id');
	$q->addWhere('project_id = ' . $project_id);
	list($project) = $q->loadList();
	$output = '
<Project>
	<Name>' .  $project['project_name'] . '.xml</Name>
	<Title>' .  $project['project_name'] . '</Title>
	<Company>' . $project['company_name'] . '</Company>
	<Author>' . $project['owner'] . '</Author>
	<CreationDate></CreationDate>
	<LastSaved>' . date('Y-m-d\TH:i:s') . '</LastSaved>
	<ScheduleFromStart>1</ScheduleFromStart>
	<StartDate>' . $project['project_start_date'] . '</StartDate>
	<FinishDate>' . $project['project_end_date'] . '</FinishDate>
	<FYStartDate>1</FYStartDate>
	<CriticalSlackLimit>0</CriticalSlackLimit>
	<CurrencyDigits>2</CurrencyDigits>
	<CurrencySymbol>$</CurrencySymbol>
	<CurrencySymbolPosition>0</CurrencySymbolPosition>
	<CalendarUID>1</CalendarUID>	
	<DefaultStartTime>' . dPgetConfig('cal_day_start') . ':00:00' . '</DefaultStartTime>
	<DefaultFinishTime>' . dPgetConfig('cal_day_end') . ':00:00' . '</DefaultFinishTime>
	<MinutesPerDay>' . ($mins_per_day = (dPgetConfig('cal_day_end') - dPgetConfig('cal_day_start')) * 60) . '</MinutesPerDay>
	<MinutesPerWeek>' . ($mins_per_day * count(explode(',', dPgetConfig('cal_working_days')))) . '</MinutesPerWeek>
	<DaysPerMonth>20</DaysPerMonth>
	<DefaultTaskType>0</DefaultTaskType>
	<DefaultFixedCostAccrual>3</DefaultFixedCostAccrual>
	<DefaultStandardRate>0</DefaultStandardRate>
	<DefaultOvertimeRate>0</DefaultOvertimeRate>
	<DurationFormat>7</DurationFormat>
	<WorkFormat>2</WorkFormat>
	<EditableActualCosts>0</EditableActualCosts>
	<HonorConstraints>0</HonorConstraints>
	<InsertedProjectsLikeSummary>1</InsertedProjectsLikeSummary>
	<MultipleCriticalPaths>0</MultipleCriticalPaths>
	<NewTasksEffortDriven>1</NewTasksEffortDriven>
	<NewTasksEstimated>1</NewTasksEstimated>
	<SplitsInProgressTasks>1</SplitsInProgressTasks>
	<SpreadActualCost>0</SpreadActualCost>
	<SpreadPercentComplete>0</SpreadPercentComplete>
	<TaskUpdatesResource>1</TaskUpdatesResource>
	<FiscalYearStart>0</FiscalYearStart>
	<WeekStartDay>0</WeekStartDay>
	<MoveCompletedEndsBack>0</MoveCompletedEndsBack>
	<MoveRemainingStartsBack>0</MoveRemainingStartsBack>
	<MoveRemainingStartsForward>0</MoveRemainingStartsForward>
	<MoveCompletedEndsForward>0</MoveCompletedEndsForward>
	<BaselineForEarnedValue>0</BaselineForEarnedValue>
	<AutoAddNewResourcesAndTasks>1</AutoAddNewResourcesAndTasks>
	<CurrentDate>' . date('Y-m-d\TH:i:s') . '</CurrentDate>
	<MicrosoftProjectServerURL>1</MicrosoftProjectServerURL>
	<Autolink>1</Autolink>
	<NewTaskStartDate>0</NewTaskStartDate>
	<DefaultTaskEVMethod>0</DefaultTaskEVMethod>
	<ProjectExternallyEdited>0</ProjectExternallyEdited>
	<OutlineCodes/>
	<WBSMasks/>
	<ExtendedAttributes/>';
// Calendars 
	$output .= '
	<Calendars>';
	$output .= dumpCalendar();
	$output .= '
	</Calendars>';

// Tasks
	$q->clear();
	$q->addQuery('task_id, task_name');
	$q->addQuery('task_start_date, task_end_date');
	$q->addQuery('task_duration, task_duration_type');
	$q->addQuery('task_priority, task_status');
	$q->addQuery('task_hours_worked');
	$q->addQuery('task_percent_complete');

	$q->addTable('tasks');
	$q->addWhere('task_project = ' . $project_id);
	$tasks = $q->loadList();
	
	$output .= '
	<Tasks>';
	foreach($tasks as $task)
		$output .= dumpTask($task);
	$output .= '
	</Tasks>';
	$output .= '
	<Assignments>
	</Assignments>';

	$output .= '
</Project>';

	return $output;
}

function dumpCalendar()
{
	$output = '
	<Calendar>
		<UID>1</UID>
		<Name>Standard</Name>
		<IsBaseCalendar>1</IsBaseCalendar>
		<BaseCalendarUID>-1</BaseCalendarUID>
		<WeekDays>';
		
	$working_days = dPgetConfig('cal_working_days');
	for ($i = 1; $i <= 7; $i++)
	{
		$output .= '
			<WeekDay>
				<DayType>' . $i . '</DayType>';
		if (strstr($working_days, $i.''))
			$output .= '
				<DayWorking>1</DayWorking>
					<WorkingTimes>
						<WorkingTime>
							<FromTime>' . dPgetConfig('cal_day_start') . ':00:00' . '</FromTime>
							<ToTime>' . dPgetConfig('cal_day_end') . ':00:00' . '</ToTime>
						</WorkingTime>
					</WorkingTimes>';
		else
			$output .= '
				<DayWorking>0</DayWorking>';

		$output .= '
			</WeekDay>';
	} 
	$output .= '
		</WeekDays>
	</Calendar>';		
	
	return $output;
}
?>