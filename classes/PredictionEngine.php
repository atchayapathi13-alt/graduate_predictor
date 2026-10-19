<?php
/**
 * Prediction Engine
 * Predicts graduate outcome readiness category, calculates transparent confidence,
 * and extracts dynamic strengths and areas requiring improvement.
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';
require_once __DIR__ . '/AnalysisEngine.php';

class PredictionEngine {
    private PDO $db;
    private AnalysisEngine $analysisEngine;

    public function __construct() {
        $this->db = getDB();
        $this->analysisEngine = new AnalysisEngine();
    }

    /**
     * Run prediction and strength/weakness analysis for a student
     */
    public function predictStudentOutcome(int $studentId): array {
        // First ensure up-to-date score analysis
        $scores = $this->analysisEngine->calculateStudentAnalysis($studentId);

        // Fetch student profile, academic, and activity data
        $academic = $this->getAcademicRecord($studentId);
        $internships = $this->getInternships($studentId);
        $projects = $this->getProjects($studentId);
        $certs = $this->getCertifications($studentId);
        $skills = $this->getSkillsSummary($studentId);
        $activities = $this->getExtracurriculars($studentId);

        $overall = $scores['overall_score'];
        $academicScore = $scores['academic_score'];
        $technicalScore = $scores['technical_score'];
        $careerScore = $scores['career_readiness_score'];
        $softScore = $scores['soft_skills_score'];
        $backlogs = (int) ($academic['num_backlogs'] ?? 0);
        $attendance = (float) ($academic['attendance_percentage'] ?? 0);
        $cgpa = (float) ($academic['current_cgpa'] ?? 0);

        // Configurable thresholds from settings
        $highThreshold = (float) getSetting('high_readiness_threshold', 75);
        $modThreshold  = (float) getSetting('moderate_readiness_threshold', 50);

        // Decision logic based on multi-criteria academic rules
        if ($overall >= $highThreshold && $backlogs === 0 && $attendance >= 75 && $technicalScore >= 65) {
            $category = 'High Readiness';
            $summary = "High probability of securing desirable placement offers and smoothly transitioning into professional graduate roles.";
        } elseif ($overall >= $modThreshold && $backlogs <= 1) {
            $category = 'Moderate Readiness';
            $summary = "Competitive candidate profile with solid fundamentals. Profile will significantly improve with targeted focus on weak domains.";
        } else {
            $category = 'Needs Improvement';
            $summary = "Student requires focused academic intervention, backlog remediation, and hands-on skill development to meet industry readiness standards.";
        }

        // Transparent Confidence / Profile Completeness Index:
        // Evaluates data completeness across 6 dimensions + consistency
        $dataPointsFilled = 0;
        $totalDimensions = 6;
        if (!empty($academic) && $cgpa > 0) $dataPointsFilled++;
        if ($attendance > 0) $dataPointsFilled++;
        if (!empty($skills['technical_count']) && $skills['technical_count'] >= 5) $dataPointsFilled++;
        if (!empty($skills['soft_count']) && $skills['soft_count'] >= 3) $dataPointsFilled++;
        if (!empty($projects)) $dataPointsFilled++;
        if (!empty($certs) || !empty($internships)) $dataPointsFilled++;

        $completenessRatio = ($dataPointsFilled / $totalDimensions);
        // Base confidence is bounded between 70% and 96% based on completeness
        $confidence = round(70.0 + ($completenessRatio * 26.0), 1);

        // Dynamic Strengths Identification
        $strengths = [];
        if ($cgpa >= 8.0) {
            $strengths[] = "High academic consistency with a CGPA of " . number_format($cgpa, 2) . " / 10.0.";
        }
        if ($attendance >= 85.0) {
            $strengths[] = "Strong lecture and laboratory attendance rate (" . number_format($attendance, 1) . "%).";
        }
        if ($backlogs === 0) {
            $strengths[] = "Clean academic record with zero active backlogs.";
        }
        if ($technicalScore >= 75.0) {
            $strengths[] = "Solid proficiency across core technical and programming domains.";
        }
        if (!empty($internships)) {
            $internCount = count($internships);
            $strengths[] = "Practical industry experience through $internCount completed internship(s).";
        }
        if (count($certs) >= 2) {
            $strengths[] = "Holds " . count($certs) . " verified industry-recognized certifications.";
        }
        if (count($projects) >= 2) {
            $strengths[] = "Hands-on development portfolio with " . count($projects) . " completed project(s).";
        }
        if ($softScore >= 75.0) {
            $strengths[] = "Strong communication, teamwork, and interpersonal competencies.";
        }
        if (!empty($activities)) {
            $strengths[] = "Active involvement in hackathons, competitions, or co-curricular clubs.";
        }

        // If very few strengths identified, provide positive baseline encouragement
        if (empty($strengths)) {
            $strengths[] = "Enrolled and actively participating in degree coursework.";
        }

        // Dynamic Areas Requiring Improvement (Weaknesses)
        $weaknesses = [];
        if ($attendance < 75.0) {
            $weaknesses[] = "Attendance is currently at " . number_format($attendance, 1) . "%, below the recommended 75% university benchmark.";
        }
        if ($cgpa < 7.0 && $cgpa > 0) {
            $weaknesses[] = "Current CGPA of " . number_format($cgpa, 2) . " is below the standard campus placement screening threshold of 7.0.";
        }
        if ($backlogs > 0) {
            $weaknesses[] = "Presence of $backlogs active backlog course(s) that directly impact placement eligibility.";
        }
        if ($technicalScore < 60.0) {
            $weaknesses[] = "Technical skill and coding proficiency score is low (" . number_format($technicalScore, 1) . "%).";
        }
        if (empty($internships)) {
            $weaknesses[] = "No corporate or research internship experience recorded yet.";
        }
        if (count($certs) < 1) {
            $weaknesses[] = "No verified technical certifications to substantiate domain competencies.";
        }
        if (count($projects) < 2) {
            $weaknesses[] = "Limited practical project repository (recommended minimum of 2 well-documented projects).";
        }
        if ($softScore < 65.0) {
            $weaknesses[] = "Soft skills and presentation self-rating indicate potential difficulty in interview rounds.";
        }

        if (empty($weaknesses)) {
            $weaknesses[] = "Profile is well-rounded. Continue fine-tuning advanced specialization skills and interview prep.";
        }

        $result = [
            'student_id'        => $studentId,
            'category'          => $category,
            'confidence_score'  => $confidence,
            'prediction_notes'  => $summary,
            'overall_score'     => $overall,
            'strengths'         => $strengths,
            'weaknesses'        => $weaknesses,
            'disclaimer'        => "This prediction is an analytical estimate derived from the student's submitted academic and skill profile. It is intended as academic decision support, not an absolute guarantee of career placement."
        ];

        // Save prediction to database
        $this->savePrediction($studentId, $category, $confidence, $summary);

        return $result;
    }

    private function getAcademicRecord(int $studentId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM academic_records WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: null;
    }

    private function getInternships(int $studentId): array {
        $stmt = $this->db->prepare("SELECT * FROM internships WHERE student_id = ? AND has_internship = 1");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    private function getProjects(int $studentId): array {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    private function getCertifications(int $studentId): array {
        $stmt = $this->db->prepare("SELECT * FROM certifications WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    private function getExtracurriculars(int $studentId): array {
        $stmt = $this->db->prepare("SELECT * FROM extracurricular_activities WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    private function getSkillsSummary(int $studentId): array {
        $stmt = $this->db->prepare("
            SELECT s.category, count(*) as count, AVG(ss.rating) as avg_rating
            FROM student_skills ss
            JOIN skills s ON ss.skill_id = s.id
            WHERE ss.student_id = ?
            GROUP BY s.category
        ");
        $stmt->execute([$studentId]);
        $rows = $stmt->fetchAll();

        $techCount = 0;
        $softCount = 0;
        foreach ($rows as $r) {
            if ($r['category'] === 'soft_skill') {
                $softCount = (int) $r['count'];
            } else {
                $techCount += (int) $r['count'];
            }
        }
        return ['technical_count' => $techCount, 'soft_count' => $softCount];
    }

    private function savePrediction(int $studentId, string $category, float $confidence, string $notes): void {
        $stmt = $this->db->prepare("
            INSERT INTO predictions (student_id, category, confidence_score, prediction_notes, predicted_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            category = VALUES(category),
            confidence_score = VALUES(confidence_score),
            prediction_notes = VALUES(prediction_notes),
            predicted_at = NOW()
        ");
        $stmt->execute([$studentId, $category, $confidence, $notes]);
    }
}
