<?php
require_once __DIR__ . '/bootstrap.php';
function getCurrentPlanId($pdo, $businessId) {
    $stmt = $pdo->prepare("
        SELECT plan_id
        FROM subscriptions
        WHERE business_id=? AND status='ACTIVE'
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$businessId]);
    return $stmt->fetchColumn();
}
function getFeatureValue($pdo, $businessId, $featureKey) {
    $planId = getCurrentPlanId($pdo, $businessId);
    if (!$planId) return null;
$stmt = $pdo->prepare("
        SELECT feature_value
        FROM plan_features
        WHERE plan_id=? AND feature_key=?
        LIMIT 1
    ");
    $stmt->execute([$planId, $featureKey]);
    return $stmt->fetchColumn();
}

function enforceFeature($pdo, $businessId, $featureKey) {
    $value = getFeatureValue($pdo, $businessId, $featureKey);
    if ($value === '0' || strtolower($value) === 'false') {
        die("Feature disabled for your plan.");
    }
}
function enforceLimit($pdo, $businessId, $featureKey, $currentUsage) {
    $limit = getFeatureValue($pdo, $businessId, $featureKey);
    if (!$limit) return;
    if (strtolower($limit) === 'unlimited') return;
    if ($currentUsage >= (int)$limit) {
        die("You have reached your plan limit for {$featureKey}. Please upgrade.");
    }
}
?>
