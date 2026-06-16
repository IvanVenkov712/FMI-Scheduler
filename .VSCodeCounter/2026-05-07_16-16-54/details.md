# Details

Date : 2026-05-07 16:16:54

Directory c:\\Users\\ivanv\\FMI\\WebProject

Total : 57 files,  3804 codes, 7 comments, 860 blanks, all 4671 lines

[Summary](results.md) / Details / [Diff Summary](diff.md) / [Diff Details](diff-details.md)

## Files
| filename | language | code | comment | blank | total |
| :--- | :--- | ---: | ---: | ---: | ---: |
| [README.md](/README.md) | Markdown | 1 | 0 | 1 | 2 |
| [app/core/Controller.php](/app/core/Controller.php) | PHP | 66 | 0 | 18 | 84 |
| [app/core/Database.php](/app/core/Database.php) | PHP | 31 | 0 | 6 | 37 |
| [app/core/Model.php](/app/core/Model.php) | PHP | 21 | 0 | 4 | 25 |
| [app/core/Repository.php](/app/core/Repository.php) | PHP | 131 | 0 | 28 | 159 |
| [app/core/View.php](/app/core/View.php) | PHP | 55 | 0 | 15 | 70 |
| [app/exceptions/InsertException.php](/app/exceptions/InsertException.php) | PHP | 8 | 0 | 1 | 9 |
| [app/helpers/EventDeliveryModeHelper.php](/app/helpers/EventDeliveryModeHelper.php) | PHP | 43 | 0 | 12 | 55 |
| [app/helpers/OnlineAccessHelper.php](/app/helpers/OnlineAccessHelper.php) | PHP | 54 | 0 | 14 | 68 |
| [app/helpers/ResourceRequirementsHelper.php](/app/helpers/ResourceRequirementsHelper.php) | PHP | 42 | 0 | 10 | 52 |
| [app/helpers/SensitiveDataHelper.php](/app/helpers/SensitiveDataHelper.php) | PHP | 42 | 0 | 12 | 54 |
| [app/helpers/SessionHelper.php](/app/helpers/SessionHelper.php) | PHP | 36 | 0 | 9 | 45 |
| [app/helpers/StringHelper.php](/app/helpers/StringHelper.php) | PHP | 8 | 0 | 0 | 8 |
| [app/helpers/TimeHelper.php](/app/helpers/TimeHelper.php) | PHP | 18 | 0 | 3 | 21 |
| [app/logger/Logger.php](/app/logger/Logger.php) | PHP | 5 | 0 | 2 | 7 |
| [app/models/Building.php](/app/models/Building.php) | PHP | 13 | 0 | 3 | 16 |
| [app/models/Event.php](/app/models/Event.php) | PHP | 26 | 0 | 5 | 31 |
| [app/models/Floor.php](/app/models/Floor.php) | PHP | 12 | 0 | 3 | 15 |
| [app/models/MovementConstraint.php](/app/models/MovementConstraint.php) | PHP | 13 | 0 | 3 | 16 |
| [app/models/OnlineAccess.php](/app/models/OnlineAccess.php) | PHP | 11 | 0 | 4 | 15 |
| [app/models/Resource.php](/app/models/Resource.php) | PHP | 8 | 0 | 2 | 10 |
| [app/models/ResourceRequirement.php](/app/models/ResourceRequirement.php) | PHP | 10 | 0 | 4 | 14 |
| [app/models/Room.php](/app/models/Room.php) | PHP | 14 | 0 | 4 | 18 |
| [app/models/RoomResource.php](/app/models/RoomResource.php) | PHP | 8 | 0 | 2 | 10 |
| [app/models/RouteSegment.php](/app/models/RouteSegment.php) | PHP | 14 | 0 | 4 | 18 |
| [app/models/Schedule.php](/app/models/Schedule.php) | PHP | 11 | 0 | 1 | 12 |
| [app/models/ScheduleEvent.php](/app/models/ScheduleEvent.php) | PHP | 8 | 0 | 1 | 9 |
| [app/models/User.php](/app/models/User.php) | PHP | 14 | 0 | 3 | 17 |
| [app/repositories/BuildingRepository.php](/app/repositories/BuildingRepository.php) | PHP | 24 | 0 | 5 | 29 |
| [app/repositories/EventRepository.php](/app/repositories/EventRepository.php) | PHP | 143 | 0 | 24 | 167 |
| [app/repositories/FloorRepository.php](/app/repositories/FloorRepository.php) | PHP | 35 | 0 | 7 | 42 |
| [app/repositories/MovementConstraintRepository.php](/app/repositories/MovementConstraintRepository.php) | PHP | 29 | 0 | 5 | 34 |
| [app/repositories/OnlineAccessRepository.php](/app/repositories/OnlineAccessRepository.php) | PHP | 9 | 0 | 2 | 11 |
| [app/repositories/ResourceRepository.php](/app/repositories/ResourceRepository.php) | PHP | 9 | 0 | 1 | 10 |
| [app/repositories/RoomRepository.php](/app/repositories/RoomRepository.php) | PHP | 137 | 4 | 27 | 168 |
| [app/repositories/RouteRepository.php](/app/repositories/RouteRepository.php) | PHP | 36 | 0 | 11 | 47 |
| [app/repositories/ScheduleRepository.php](/app/repositories/ScheduleRepository.php) | PHP | 61 | 0 | 10 | 71 |
| [app/repositories/UserRepository.php](/app/repositories/UserRepository.php) | PHP | 83 | 0 | 22 | 105 |
| [app/services/AuthorizationService.php](/app/services/AuthorizationService.php) | PHP | 81 | 0 | 25 | 106 |
| [app/services/EventApprovalService.php](/app/services/EventApprovalService.php) | PHP | 79 | 0 | 22 | 101 |
| [app/services/EventRequestService.php](/app/services/EventRequestService.php) | PHP | 166 | 0 | 41 | 207 |
| [app/services/ExamSessionSetupService.php](/app/services/ExamSessionSetupService.php) | PHP | 246 | 0 | 49 | 295 |
| [app/services/ExportService.php](/app/services/ExportService.php) | PHP | 251 | 0 | 48 | 299 |
| [app/services/ImportService.php](/app/services/ImportService.php) | PHP | 337 | 0 | 65 | 402 |
| [app/services/LoginService.php](/app/services/LoginService.php) | PHP | 33 | 0 | 8 | 41 |
| [app/services/MovementConstraintService.php](/app/services/MovementConstraintService.php) | PHP | 137 | 0 | 34 | 171 |
| [app/services/OnlineAccessService.php](/app/services/OnlineAccessService.php) | PHP | 84 | 0 | 23 | 107 |
| [app/services/RegisterService.php](/app/services/RegisterService.php) | PHP | 51 | 3 | 15 | 69 |
| [app/services/RoomAllocationService.php](/app/services/RoomAllocationService.php) | PHP | 124 | 0 | 33 | 157 |
| [app/services/RoomAvailabilityService.php](/app/services/RoomAvailabilityService.php) | PHP | 97 | 0 | 30 | 127 |
| [app/services/RoomResourceService.php](/app/services/RoomResourceService.php) | PHP | 72 | 0 | 19 | 91 |
| [app/services/RoutePlanningService.php](/app/services/RoutePlanningService.php) | PHP | 167 | 0 | 40 | 207 |
| [app/services/RouteSegmentService.php](/app/services/RouteSegmentService.php) | PHP | 34 | 0 | 9 | 43 |
| [app/services/SchedulePublishingService.php](/app/services/SchedulePublishingService.php) | PHP | 93 | 0 | 26 | 119 |
| [app/services/UserEventAccessService.php](/app/services/UserEventAccessService.php) | PHP | 92 | 0 | 25 | 117 |
| [database/schema.sql](/database/schema.sql) | MS SQL | 187 | 0 | 16 | 203 |
| [services.md](/services.md) | Markdown | 184 | 0 | 44 | 228 |

[Summary](results.md) / Details / [Diff Summary](diff.md) / [Diff Details](diff-details.md)