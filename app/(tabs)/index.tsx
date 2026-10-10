import React, { useEffect, useState, useCallback, useRef } from 'react';
import {
  View, Text, ScrollView, StyleSheet, RefreshControl,
  TouchableOpacity, ActivityIndicator, Modal, Pressable, Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useFocusEffect } from 'expo-router';
import { Card } from '@/components/ui/Card';
import { StatCard } from '@/components/ui/StatCard';
import { Badge } from '@/components/ui/Badge';
import { useAuth } from '@/hooks/useAuth';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type DashboardData = {
  summary: {
    projects: number;
    active_projects: number;
    completed_projects: number;
    pending_works: number;
    clients: number;
    locations: number;
  };
  todays_meetings: Array<{ id: number; name: string; client: string; next_meeting_at: string; next_meeting_notes: string | null }>;
  upcoming_meetings: Array<{ id: number; name: string; client: string; next_meeting_at: string; next_meeting_notes: string | null }>;
  recent_projects: Array<{ id: number; name: string; project_number: string; status: string; client: string }>;
  upcoming_deadlines: Array<{ type: string; title: string; subtitle: string | null; date: string; days: number }>;
};

function formatDate(iso: string) {
  const d = new Date(iso);
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}
function formatTime(iso: string) {
  const d = new Date(iso);
  return d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
}

export default function DashboardScreen() {
  const { user, signOut } = useAuth();
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [dropdownVisible, setDropdownVisible] = useState(false);
  const [dropdownPos, setDropdownPos] = useState({ top: 100, right: 16 });
  const profileBtnRef = useRef<TouchableOpacity>(null);

  const initials = (user?.name ?? '?')
    .split(' ').filter(Boolean).slice(0, 2)
    .map((w) => w[0].toUpperCase()).join('');

  function openDropdown() {
    profileBtnRef.current?.measureInWindow((_x, y, _w, h) => {
      setDropdownPos({ top: y + h + 6, right: 16 });
      setDropdownVisible(true);
    });
  }

  function handleSignOut() {
    setDropdownVisible(false);
    Alert.alert('Sign Out', 'Are you sure you want to sign out?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Sign Out', style: 'destructive', onPress: signOut },
    ]);
  }

  const load = useCallback(async () => {
    try {
      const res = await api.get('/mobile/dashboard');
      setData(res.data);
    } catch {
      // keep showing last data on error
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));
  useEffect(() => { load(); }, [load]);

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={Colors.accent} />
      </View>
    );
  }

  const s = data?.summary;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
        showsVerticalScrollIndicator={false}
      >
        {/* Greeting */}
        <View style={styles.greeting}>
          <View style={{ flex: 1, marginRight: 12 }}>
            <Text style={styles.greetingText}>Welcome, {user?.name?.split(' ')[0]} 👋</Text>
            <Text style={styles.greetingDate}>{new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' })}</Text>
          </View>
          <TouchableOpacity ref={profileBtnRef} onPress={openDropdown} style={styles.profileTrigger} activeOpacity={0.8}>
            <View style={styles.profileAvatar}>
              <Text style={styles.profileInitials}>{initials}</Text>
            </View>
            <Ionicons name="chevron-down" size={14} color={Colors.textSecondary} style={{ marginLeft: 4 }} />
          </TouchableOpacity>
        </View>

        {/* Profile dropdown */}
        <Modal visible={dropdownVisible} transparent animationType="fade" onRequestClose={() => setDropdownVisible(false)}>
          <Pressable style={styles.dropdownOverlay} onPress={() => setDropdownVisible(false)}>
            <View style={[styles.dropdownCard, { top: dropdownPos.top, right: dropdownPos.right }]}>
              {/* Header */}
              <View style={styles.dropdownHeader}>
                <View style={styles.dropdownAvatar}>
                  <Text style={styles.dropdownAvatarText}>{initials}</Text>
                </View>
                <View style={{ marginLeft: 10 }}>
                  <Text style={styles.dropdownName}>{user?.name}</Text>
                  <Text style={styles.dropdownRole}>{user?.is_admin ? 'Admin' : 'Contractor'}</Text>
                </View>
              </View>

              <View style={styles.dropdownDivider} />

              {/* Edit Profile */}
              <TouchableOpacity
                style={styles.dropdownRow}
                onPress={() => { setDropdownVisible(false); router.push('/(tabs)/profile'); }}
                activeOpacity={0.7}
              >
                <Ionicons name="person-outline" size={18} color={Colors.text} style={styles.dropdownIcon} />
                <Text style={styles.dropdownRowText}>Edit Profile</Text>
              </TouchableOpacity>

              <View style={styles.dropdownDivider} />

              {/* Change Password */}
              <TouchableOpacity
                style={styles.dropdownRow}
                onPress={() => { setDropdownVisible(false); router.push('/(tabs)/change-password'); }}
                activeOpacity={0.7}
              >
                <Ionicons name="key-outline" size={18} color={Colors.text} style={styles.dropdownIcon} />
                <Text style={styles.dropdownRowText}>Change Password</Text>
              </TouchableOpacity>

              <View style={styles.dropdownDivider} />

              {/* Sign Out */}
              <TouchableOpacity style={styles.dropdownRow} onPress={handleSignOut} activeOpacity={0.7}>
                <Ionicons name="log-out-outline" size={18} color={Colors.danger} style={styles.dropdownIcon} />
                <Text style={[styles.dropdownRowText, { color: Colors.danger }]}>Sign Out</Text>
              </TouchableOpacity>
            </View>
          </Pressable>
        </Modal>

        {/* Stat cards */}
        <View style={styles.statsRow}>
          <StatCard label="Projects" value={s?.projects ?? 0} accent="primary" style={{ marginRight: 6 }} />
          <StatCard label="Active" value={s?.active_projects ?? 0} accent="success" style={{ marginLeft: 6 }} />
        </View>
        <View style={styles.statsRow}>
          <StatCard label="Pending Works" value={s?.pending_works ?? 0} accent="warning" style={{ marginRight: 6 }} />
          <StatCard label="Clients" value={s?.clients ?? 0} accent="info" style={{ marginLeft: 6 }} />
        </View>

        {/* Today's meetings */}
        <Card title="📅 Today's Meetings" style={styles.meetingCard}>
          {(data?.todays_meetings?.length ?? 0) === 0 ? (
            <View style={styles.emptyMeeting}>
              <Ionicons name="calendar-outline" size={28} color={Colors.textMuted} />
              <Text style={styles.emptyMeetingText}>No meetings scheduled for today</Text>
            </View>
          ) : (
            data!.todays_meetings.map((m) => (
              <TouchableOpacity key={m.id} style={styles.meetingItem} onPress={() => router.push(`/(tabs)/projects/${m.id}`)}>
                <View style={styles.meetingTime}>
                  <Text style={styles.meetingTimeText}>{formatTime(m.next_meeting_at)}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.meetingTitle} numberOfLines={1}>{m.name}</Text>
                  <Text style={styles.meetingMeta} numberOfLines={1}>{m.client}{m.next_meeting_notes ? ` · ${m.next_meeting_notes}` : ''}</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} />
              </TouchableOpacity>
            ))
          )}
        </Card>

        {/* Upcoming meetings */}
        <Card title="🗓 Upcoming Meetings">
          {(data?.upcoming_meetings?.length ?? 0) === 0 ? (
            <View style={styles.emptyMeeting}>
              <Ionicons name="time-outline" size={28} color={Colors.textMuted} />
              <Text style={styles.emptyMeetingText}>No upcoming meetings scheduled</Text>
            </View>
          ) : (
            data!.upcoming_meetings.map((m) => (
              <TouchableOpacity key={m.id} style={styles.meetingItem} onPress={() => router.push(`/(tabs)/projects/${m.id}`)}>
                <View style={styles.dateBox}>
                  <Text style={styles.dateDay}>{new Date(m.next_meeting_at).getDate()}</Text>
                  <Text style={styles.dateMonth}>{new Date(m.next_meeting_at).toLocaleDateString('en-IN', { month: 'short' })}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.meetingTitle} numberOfLines={1}>{m.name}</Text>
                  <Text style={styles.meetingMeta} numberOfLines={1}>{m.client} · {formatTime(m.next_meeting_at)}</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={Colors.textMuted} />
              </TouchableOpacity>
            ))
          )}
        </Card>

        {/* Recent projects */}
        <Card
          title="Recent Projects"
          titleRight={
            <TouchableOpacity onPress={() => router.push('/(tabs)/projects/index')}>
              <Text style={styles.viewAll}>View all</Text>
            </TouchableOpacity>
          }
        >
          {(data?.recent_projects ?? []).length === 0 ? (
            <Text style={styles.empty}>No projects yet.</Text>
          ) : (
            data!.recent_projects.map((p) => (
              <TouchableOpacity key={p.id} style={styles.projectRow} onPress={() => router.push(`/(tabs)/projects/${p.id}`)}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.projectName} numberOfLines={1}>{p.name}</Text>
                  <Text style={styles.projectSub}>{p.project_number} · {p.client}</Text>
                </View>
                <Badge status={p.status} />
              </TouchableOpacity>
            ))
          )}
        </Card>

        {/* Upcoming deadlines */}
        {(data?.upcoming_deadlines?.length ?? 0) > 0 && (
          <Card title="⚠️ Upcoming Deadlines">
            {data!.upcoming_deadlines.map((d, i) => (
              <View key={i} style={styles.deadlineRow}>
                <View style={styles.dateBox}>
                  <Text style={styles.dateDay}>{new Date(d.date).getDate()}</Text>
                  <Text style={styles.dateMonth}>{new Date(d.date).toLocaleDateString('en-IN', { month: 'short' })}</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.projectName} numberOfLines={1}>{d.title}</Text>
                  {d.subtitle && <Text style={styles.projectSub} numberOfLines={1}>{d.type} · {d.subtitle}</Text>}
                </View>
                <Badge
                  status={d.days < 0 ? 'high' : d.days <= 2 ? 'medium' : 'low'}
                  label={d.days < 0 ? `${Math.abs(d.days)}d late` : d.days === 0 ? 'Today' : `${d.days}d`}
                />
              </View>
            ))}
          </Card>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  scroll: { flex: 1 },
  content: { padding: 16, paddingBottom: 32 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.background },
  greeting: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  greetingText: { fontSize: 20, fontWeight: '800', color: Colors.text },
  greetingDate: { fontSize: 13, color: Colors.textSecondary, marginTop: 2 },
  profileTrigger: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.white, borderRadius: 24,
    paddingHorizontal: 10, paddingVertical: 6,
    borderWidth: 1.5, borderColor: Colors.border,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.08, shadowRadius: 3, elevation: 2,
  },
  profileAvatar: {
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: Colors.accent, alignItems: 'center', justifyContent: 'center',
  },
  profileInitials: { fontSize: 13, fontWeight: '800', color: Colors.primary },
  dropdownOverlay: { flex: 1 },
  dropdownCard: {
    position: 'absolute', minWidth: 220,
    backgroundColor: Colors.white, borderRadius: 12,
    shadowColor: '#000', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.18, shadowRadius: 16, elevation: 16,
    borderWidth: 1, borderColor: Colors.border,
    overflow: 'hidden',
  },
  dropdownHeader: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 16, paddingVertical: 14,
    backgroundColor: Colors.background,
  },
  dropdownAvatar: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: Colors.accent, alignItems: 'center', justifyContent: 'center',
  },
  dropdownAvatarText: { fontSize: 14, fontWeight: '800', color: Colors.primary },
  dropdownName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  dropdownRole: { fontSize: 12, color: Colors.textSecondary, marginTop: 1 },
  dropdownDivider: { height: 1, backgroundColor: Colors.border },
  dropdownRow: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 16, paddingVertical: 14,
    backgroundColor: Colors.white,
  },
  dropdownIcon: { marginRight: 12 },
  dropdownRowText: { fontSize: 15, fontWeight: '500', color: Colors.text },
  statsRow: { flexDirection: 'row', marginBottom: 8 },
  meetingCard: { borderLeftWidth: 3, borderLeftColor: Colors.accent },
  meetingItem: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border },
  meetingTime: { backgroundColor: Colors.accent + '20', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, marginRight: 12, minWidth: 72, alignItems: 'center' },
  meetingTimeText: { fontSize: 13, fontWeight: '700', color: Colors.accent },
  meetingTitle: { fontSize: 14, fontWeight: '700', color: Colors.text },
  meetingMeta: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  dateBox: { alignItems: 'center', backgroundColor: Colors.primary + '10', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, marginRight: 12, minWidth: 48 },
  dateDay: { fontSize: 18, fontWeight: '800', color: Colors.primary },
  dateMonth: { fontSize: 10, fontWeight: '600', color: Colors.textSecondary, textTransform: 'uppercase' },
  viewAll: { fontSize: 13, color: Colors.accent, fontWeight: '600' },
  projectRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border },
  projectName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  projectSub: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  deadlineRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: Colors.border },
  empty: { color: Colors.textMuted, fontSize: 13, textAlign: 'center', paddingVertical: 16 },
  emptyMeeting: { alignItems: 'center', paddingVertical: 20, gap: 8 },
  emptyMeetingText: { fontSize: 13, color: Colors.textMuted, textAlign: 'center' },
});
