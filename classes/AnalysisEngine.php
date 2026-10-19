<?php
/**
 * Analysis Engine
 * Computes individual domain scores and overall performance index.
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

class AnalysisEngine {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Compute comprehensive performance analysis for a student
     */
    public function calculateStudentAnalysis(int $studentId): array {
        $academicScore       = $this->computeAcademicScore($studentId);
        $technicalScore      = $this->computeTechnicalScore($studentId);
        $softSkillsScore     = $this->computeSoftSkillsScore($studentId);
        $careerReadinessScore = $this->computeCareerReadinessScore($studentId, $technicalScore, $softSkillsScore);
        $extracurricularScore = $this->computeExtracurricularScore($studentId);

        // Fetch configurable weights from settings
        $wAcademic = (float) getSetting('academic_weight', 40);
        $wTech     = (float) getSetting('technical_weight', 25);
        $wCareer   = (float) getSetting('career_weight', 20);
        $wSoft     = (float) getSetting('soft_skills_weight', 10);
        $wExtra    = (float) getSetting('extracurricular_weight', 5);

        $totalWeight = $wAcademic + $wTech + $wCareer + $wSoft + $wExtra;
        if ($totalWeight <= 0) {
            $totalWeight = 100;
        }

        $overallScore = (
            ($academicScore * $wAcademic) +
            ($technicalScore * $wTech) +
            ($careerReadinessScore * $wCareer) +
            ($softSkillsScore * $wSoft) +
            ($extracurricularScore * $wExtra)
        ) / $totalWeight;

        $overallScore = round(min(100.0, max(0.0, $overallScore)), 2);

        $analysis = [
            'student_id'             => $studentId,
            'academic_score'         => round($academicScore, 2),
            'technical_score'        => round($technicalScore, 2),
            'career_readiness_score' => round($careerReadinessScore, 2),
            'soft_skills_score'      => round($softSkillsScore, 2),
            'extracurricular_score'  => round($extracurricularScore, 2),
            'overall_score'          => $overallScore,
            'weights' => [
                'academic'        => $wAcademic,
                'technical'       => $wTech,
                'career'          => $wCareer,
                'soft_skills'     => $wSoft,
                'extracurricular' => $wExtra
            ],
            'breakdown' => [
                'academic' => $this->getAcademicBreakdown($studentId),
                'technical' => $this->getTechnicalBreakdown($studentId),
                'career' => $this->getCareerBreakdown($studentId),
                'soft_skills' => $this->getSoftSkillsBreakdown($studentId),
                'extracurricular' => $this->getExtracurricularBreakdown($studentId)
            ]
        ];

        // Persist to database
        $this->saveAnalysis($studentId, $analysis);

        return $analysis;
    }

    /**
     * Compute Academic Score (0-100)
     * Factors: CGPA (50%), Attendance (25%), School/Prior Base (15%), Backlogs (10%)
     */
    private function computeAcademicScore(int $studentId): float {
        $stmt = $this->db->prepare("SELECT * FROM academic_records WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $record = $stmt->fetch();

        if (!$record) {
            return 0.0;
        }

        $cgpa = (float) $record['current_cgpa'];
        $attendance = (float) $record['attendance_percentage'];
        $tenth = (float) $record['tenth_percentage'];
        $twelfth = !empty($record['twelfth_percentage']) ? (float)$record['twelfth_percentage'] : (float)$record['diploma_percentage'];
        $backlogs = (int) $record['num_backlogs'];

        // 1. CGPA component (Max 10 => 50 pts)
        $cgpaPoints = (min(10.0, max(0.0, $cgpa)) / 10.0) * 50.0;

        // 2. Attendance component (Max 100% => 25 pts)
        $attendancePoints = (min(100.0, max(0.0, $attendance)) / 100.0) * 25.0;

        // 3. Prior Academic foundation (10th + 12th avg => 15 pts)
        $priorAvg = ($tenth + ($twelfth ?: $tenth)) / 2.0;
        $priorPoints = (min(100.0, max(0.0, $priorAvg)) / 100.0) * 15.0;

        // 4. Backlog component (10 pts default, -3 pts per backlog, min 0)
        $backlogPoints = max(0.0, 10.0 - ($backlogs * 3.0));

        return min(100.0, max(0.0, $cgpaPoints + $attendancePoints + $priorPoints + $backlogPoints));
    }

    /**
     * Compute Technical Skill Score (0-100)
     * Factors: Technical skill ratings (50%), Projects (30%), Certifications (20%)
     */
    private function computeTechnicalScore(int $studentId): float {
        // 1. Skill ratings for programming/technical
        $stmt = $this->db->prepare("
            SELECT ss.rating 
            FROM student_skills ss
            JOIN skills s ON ss.skill_id = s.id
            WHERE ss.student_id = ? AND s.category IN ('programming', 'technical')
        ");
        $stmt->execute([$studentId]);
        $ratings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $skillRatingPoints = 0.0;
        if (!empty($ratings)) {
            $avgRating = array_sum($ratings) / count($ratings); // 1.0 to 5.0
            $skillRatingPoints = (($avgRating - 1.0) / 4.0) * 50.0; // scale 1-5 to 0-50
        }

        // 2. Projects factor (30 pts max)
        $stmtProj = $this->db->prepare("SELECT complexity, status FROM projects WHERE student_id = ?");
        $stmtProj->execute([$studentId]);
        $projects = $stmtProj->fetchAll();

        $projectPoints = 0.0;
        foreach ($projects as $proj) {
            $base = ($proj['status'] === 'Completed') ? 10.0 : 6.0;
            if ($proj['complexity'] === 'Advanced') {
                $base += 5.0;
            } elseif ($proj['complexity'] === 'Intermediate') {
                $base += 2.0;
            }
            $projectPoints += $base;
        }
        $projectPoints = min(30.0, $projectPoints);

        // 3. Certifications factor (20 pts max)
        $stmtCert = $this->db->prepare("SELECT certification_level FROM certifications WHERE student_id = ?");
        $stmtCert->execute([$studentId]);
        $certs = $stmtCert->fetchAll();

        $certPoints = 0.0;
        foreach ($certs as $cert) {
            $pts = 6.0;
            if ($cert['certification_level'] === 'Advanced' || $cert['certification_level'] === 'Professional') {
                $pts = 10.0;
            } elseif ($cert['certification_level'] === 'Intermediate') {
                $pts = 8.0;
            }
            $certPoints += $pts;
        }
        $certPoints = min(20.0, $certPoints);

        return min(100.0, max(0.0, $skillRatingPoints + $projectPoints + $certPoints));
    }

    /**
     * Compute Soft Skills Score (0-100)
     */
    private function computeSoftSkillsScore(int $studentId): float {
        $stmt = $this->db->prepare("
            SELECT ss.rating 
            FROM student_skills ss
            JOIN skills s ON ss.skill_id = s.id
            WHERE ss.student_id = ? AND s.category = 'soft_skill'
        ");
        $stmt->execute([$studentId]);
        $ratings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($ratings)) {
            return 50.0; // Default median if not filled
        }

        $avg = array_sum($ratings) / count($ratings); // 1.0 - 5.0
        // Scaled to percentage: 1=20%, 5=100%
        return min(100.0, max(0.0, ($avg / 5.0) * 100.0));
    }

    /**
     * Compute Career Readiness Score (0-100)
     * Factors: Internship (40 pts), Projects readiness (25 pts), Industry Certs (15 pts), Soft skills (20 pts)
     */
    private function computeCareerReadinessScore(int $studentId, float $techScore, float $softScore): float {
        // 1. Internship (40 pts max)
        $stmt = $this->db->prepare("SELECT * FROM internships WHERE student_id = ? AND has_internship = 1");
        $stmt->execute([$studentId]);
        $internships = $stmt->fetchAll();

        $internshipPoints = 0.0;
        if (!empty($internships)) {
            $internshipPoints = 30.0;
            foreach ($internships as $intern) {
                $duration = (int) $intern['duration_months'];
                if ($duration >= 3) {
                    $internshipPoints = 40.0;
                    break;
                } elseif ($duration >= 2) {
                    $internshipPoints = 36.0;
                }
            }
        }

        // 2. Project Portfolio Readiness (25 pts max)
        $stmtP = $this->db->prepare("SELECT count(*) FROM projects WHERE student_id = ? AND project_url IS NOT NULL AND project_url != ''");
        $stmtP->execute([$studentId]);
        $repoProjects = (int) $stmtP->fetchColumn();
        $projectPoints = min(25.0, $repoProjects * 10.0);

        // 3. Industry Certifications (15 pts max)
        $stmtC = $this->db->prepare("SELECT count(*) FROM certifications WHERE student_id = ?");
        $stmtC->execute([$studentId]);
        $certCount = (int) $stmtC->fetchColumn();
        $certPoints = min(15.0, $certCount * 6.0);

        // 4. Soft Skills & Aptitude Factor (20 pts max)
        $softSkillsFactor = ($softScore / 100.0) * 20.0;

        return min(100.0, max(0.0, $internshipPoints + $projectPoints + $certPoints + $softSkillsFactor));
    }

    /**
     * Compute Extracurricular Activities Score (0-100)
     */
    private function computeExtracurricularScore(int $studentId): float {
        $stmt = $this->db->prepare("SELECT * FROM extracurricular_activities WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $activities = $stmt->fetchAll();

        if (empty($activities)) {
            return 20.0; // Baseline
        }

        $points = 20.0; // Baseline participation
        foreach ($activities as $act) {
            $ach = strtolower($act['achievement_level'] ?? '');
            if (str_contains($ach, 'winner') || str_contains($ach, '1st') || str_contains($ach, 'top')) {
                $points += 25.0;
            } elseif (str_contains($ach, 'runner') || str_contains($ach, '2nd') || str_contains($ach, 'finalist') || str_contains($ach, 'lead')) {
                $points += 18.0;
            } else {
                $points += 10.0;
            }
        }

        return min(100.0, max(0.0, $points));
    }

    private function getAcademicBreakdown(int $studentId): array {
        $stmt = $this->db->prepare("SELECT * FROM academic_records WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $r = $stmt->fetch() ?: [];
        return [
            'cgpa' => $r['current_cgpa'] ?? 0,
            'attendance' => $r['attendance_percentage'] ?? 0,
            'backlogs' => $r['num_backlogs'] ?? 0,
            'tenth' => $r['tenth_percentage'] ?? 0,
            'twelfth' => $r['twelfth_percentage'] ?? $r['diploma_percentage'] ?? 0
        ];
    }

    private function getTechnicalBreakdown(int $studentId): array {
        $stmtProj = $this->db->prepare("SELECT count(*) FROM projects WHERE student_id = ?");
        $stmtProj->execute([$studentId]);
        $projCount = (int) $stmtProj->fetchColumn();

        $stmtCert = $this->db->prepare("SELECT count(*) FROM certifications WHERE student_id = ?");
        $stmtCert->execute([$studentId]);
        $certCount = (int) $stmtCert->fetchColumn();

        return [
            'project_count' => $projCount,
            'certification_count' => $certCount
        ];
    }

    private function getCareerBreakdown(int $studentId): array {
        $stmt = $this->db->prepare("SELECT count(*) FROM internships WHERE student_id = ? AND has_internship = 1");
        $stmt->execute([$studentId]);
        $internCount = (int) $stmt->fetchColumn();
        return [
            'has_internship' => $internCount > 0,
            'internship_count' => $internCount
        ];
    }

    private function getSoftSkillsBreakdown(int $studentId): array {
        $stmt = $this->db->prepare("
            SELECT s.name, ss.rating 
            FROM student_skills ss
            JOIN skills s ON ss.skill_id = s.id
            WHERE ss.student_id = ? AND s.category = 'soft_skill'
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    private function getExtracurricularBreakdown(int $studentId): array {
        $stmt = $this->db->prepare("SELECT count(*) FROM extracurricular_activities WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return [
            'activity_count' => (int) $stmt->fetchColumn()
        ];
    }

    /**
     * Persist computed analysis to performance_analysis table
     */
    private function saveAnalysis(int $studentId, array $analysis): void {
        $stmt = $this->db->prepare("
            INSERT INTO performance_analysis 
            (student_id, academic_score, technical_score, career_readiness_score, soft_skills_score, extracurricular_score, overall_score, calculated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            academic_score = VALUES(academic_score),
            technical_score = VALUES(technical_score),
            career_readiness_score = VALUES(career_readiness_score),
            soft_skills_score = VALUES(soft_skills_score),
            extracurricular_score = VALUES(extracurricular_score),
            overall_score = VALUES(overall_score),
            calculated_at = NOW()
        ");
        $stmt->execute([
            $studentId,
            $analysis['academic_score'],
            $analysis['technical_score'],
            $analysis['career_readiness_score'],
            $analysis['soft_skills_score'],
            $analysis['extracurricular_score'],
            $analysis['overall_score']
        ]);
    }

    /**
     * Recalculate analysis for all students
     */
    public function recalculateAll(): int {
        $stmt = $this->db->query("SELECT id FROM students");
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($students as $sId) {
            $this->calculateStudentAnalysis((int)$sId);
        }
        return count($students);
    }
}
