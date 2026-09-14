# app/Services

App\Services — stateless classes holding cohesive business logic and queries that do not fit a single Action (e.g. TrainingSchedulingService for overlap/duration/window checks, TrainingLifecycleService for state transitions). Services may be composed of multiple Actions/queries; this is where SOLID single responsibility and dependency inversion are most deliberately applied.
