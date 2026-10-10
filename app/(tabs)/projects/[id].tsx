import React, { useCallback, useEffect, useState } from 'react';
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator,
  TouchableOpacity, RefreshControl, Linking, Modal, TextInput,
  Alert, KeyboardAvoidingView, Platform, SafeAreaView as RNSafeAreaView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useLocalSearchParams, router } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { ScreenHeader } from '@/components/ui/ScreenHeader';
import { Colors } from '@/constants/Colors';
import api from '@/services/api';

type ProjectDetail = {
  id: number;
  name: string;
  project_number: string;
  status: string;
  type_of_work: string | null;
  description: string | null;
  start_date: string | null;
  expected_end_date: string | null;
  budget: string | null;
  notes: string | null;
  next_meeting_at: string | null;
  next_meeting_notes: string | null;
  client_id: number;
  client: { name: string; phone: string | null; contact_person: string | null; company: string | null; email: string | null };
  dealer: { id: number; name: string; city: string | null; phone: string | null } | null;
  locations: Array<{ id: number; name: string; city: string; state: string; contact_person: string | null; contact_phone: string | null }>;
  works: Array<{ id: number; title: string; status: string; priority: string; due_date: string | null }>;
  meetings: Array<{ id: number; meeting_at: string; purpose: string; created_by: string | null }>;
};

type ActivityItem = {
  id: number;
  action: string;
  description: string;
  user: string | null;
  created_at: string;
};

type Dealer = { id: number; name: string; city: string | null; phone: string | null };

const WORK_STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'completed', label: 'Completed' },
];

function actionColor(action: string) {
  switch (action) {
    case 'created': return Colors.success;
    case 'deleted': return Colors.danger;
    case 'status_changed': return Colors.accent;
    case 'completed': return Colors.info;
    default: return Colors.textMuted;
  }
}

function toLocalISO(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function InlineDateTimeField({ value, onChange }: { value: string; onChange: (dt: string) => void }) {
  const [iosOpen, setIosOpen] = useState(false);
  const [iosPending, setIosPending] = useState<Date>(new Date());
  const [androidStep, setAndroidStep] = useState<'date' | 'time' | null>(null);
  const [androidDate, setAndroidDate] = useState<Date>(new Date());

  const display = value
    ? new Date(value).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })
    : '';

  function handlePress() {
    if (Platform.OS === 'android') {
      setAndroidDate(value ? new Date(value) : new Date());
      setAndroidStep('date');
    } else {
      setIosPending(value ? new Date(value) : new Date());
      setIosOpen(true);
    }
  }

  function onAndroidDate(_: any, sel?: Date) {
    if (!sel) { setAndroidStep(null); return; }
    setAndroidDate(sel);
    setAndroidStep('time');
  }

  function onAndroidTime(_: any, sel?: Date) {
    setAndroidStep(null);
    if (!sel) return;
    const combined = new Date(androidDate);
    combined.setHours(sel.getHours(), sel.getMinutes(), 0, 0);
    onChange(toLocalISO(combined));
  }

  return (
    <View>
      <TouchableOpacity style={styles.dtBtn} onPress={handlePress} activeOpacity={0.7}>
        <Ionicons name="time-outline" size={18} color={value ? Colors.text : Colors.textMuted} style={{ marginRight: 8 }} />
        <Text style={[styles.dtBtnText, !value && { color: Colors.textMuted }]}>
          {display || 'Select date & time'}
        </Text>
        {value ? (
          <TouchableOpacity onPress={() => onChange('')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }} style={{ marginLeft: 'auto' }}>
            <Ionicons name="close-circle" size={18} color={Colors.textMuted} />
          </TouchableOpacity>
        ) : (
          <Ionicons name="chevron-down" size={16} color={Colors.textMuted} style={{ marginLeft: 'auto' }} />
        )}
      </TouchableOpacity>

      {Platform.OS === 'android' && androidStep === 'date' && (
        <DateTimePicker mode="date" value={androidDate} onChange={onAndroidDate} />
      )}
      {Platform.OS === 'android' && androidStep === 'time' && (
        <DateTimePicker mode="time" value={androidDate} onChange={onAndroidTime} is24Hour={false} />
      )}

      {Platform.OS === 'ios' && iosOpen && (
        <View style={styles.iosInline}>
          <DateTimePicker
            mode="datetime"
            value={iosPending}
            onChange={(_, d) => { if (d) setIosPending(d); }}
            display="spinner"
            style={{ height: 180 }}
          />
          <View style={styles.iosInlineRow}>
            <TouchableOpacity onPress={() => setIosOpen(false)} style={styles.iosInlineBtn}>
              <Text style={styles.iosInlineCancel}>Cancel</Text>
            </TouchableOpacity>
            <TouchableOpacity onPress={() => { onChange(toLocalISO(iosPending)); setIosOpen(false); }} style={styles.iosInlineBtn}>
              <Text style={styles.iosInlineDone}>Done</Text>
            </TouchableOpacity>
          </View>
        </View>
      )}
    </View>
  );
}

function Row({ label, value, phone, email }: { label: string; value: string | null; phone?: boolean; email?: boolean }) {
  if (!value) return null;
  return (
    <View style={dl.row}>
      <Text style={dl.label}>{label}</Text>
      {phone ? (
        <TouchableOpacity onPress={() => Linking.openURL(`tel:${value}`)}>
          <Text style={[dl.value, { color: Colors.info }]}>{value}</Text>
        </TouchableOpacity>
      ) : email ? (
        <TouchableOpacity onPress={() => Linking.openURL(`mailto:${value}`)}>
          <Text style={[dl.value, { color: Colors.info }]}>{value}</Text>
        </TouchableOpacity>
      ) : (
        <Text style={dl.value}>{value}</Text>
      )}
    </View>
  );
}

function fmt(iso: string | null) {
  if (!iso) return null;
  return new Date(iso).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}
function fmtDT(iso: string | null) {
  if (!iso) return null;
  const d = new Date(iso);
  return `${d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })} · ${d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })}`;
}

export default function ProjectDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [project, setProject] = useState<ProjectDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Log Meeting modal
  const [logModal, setLogModal] = useState(false);
  const [logDate, setLogDate] = useState('');
  const [logPurpose, setLogPurpose] = useState('');
  const [logSaving, setLogSaving] = useState(false);

  // Set Next Meeting modal
  const [meetingModal, setMeetingModal] = useState(false);
  const [meetingAt, setMeetingAt] = useState('');
  const [meetingNotes, setMeetingNotes] = useState('');
  const [meetingSaving, setMeetingSaving] = useState(false);

  // Work status update
  const [updatingWork, setUpdatingWork] = useState<number | null>(null);

  // Contacts
  const [contactModal, setContactModal] = useState(false);
  const [dealers, setDealers] = useState<Dealer[]>([]);
  const [dealerSearch, setDealerSearch] = useState('');
  const [contactSaving, setContactSaving] = useState(false);

  // Activity
  const [activity, setActivity] = useState<ActivityItem[]>([]);
  const [activityLoading, setActivityLoading] = useState(false);

  const load = useCallback(async () => {
    try {
      const res = await api.get(`/mobile/projects/${id}`);
      setProject(res.data);
    } catch { /* ignore */ } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [id]);

  useEffect(() => {
    load();
    setActivityLoading(true);
    api.get(`/mobile/projects/${id}/activity`)
      .then((r) => setActivity(r.data))
      .catch(() => {})
      .finally(() => setActivityLoading(false));
  }, [load, id]);

  async function handleLogMeeting() {
    if (!logDate.trim() || !logPurpose.trim()) {
      Alert.alert('Required', 'Date and purpose are required.');
      return;
    }
    setLogSaving(true);
    try {
      await api.post(`/mobile/projects/${id}/meetings`, { meeting_at: logDate.trim(), purpose: logPurpose.trim() });
      setLogModal(false);
      setLogDate('');
      setLogPurpose('');
      load();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Failed to log meeting.');
    } finally {
      setLogSaving(false);
    }
  }

  async function handleDeleteMeeting(meetingId: number) {
    Alert.alert('Delete Meeting', 'Remove this meeting from history?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete', style: 'destructive',
        onPress: async () => {
          try {
            await api.delete(`/mobile/projects/${id}/meetings/${meetingId}`);
            load();
          } catch { Alert.alert('Error', 'Failed to delete.'); }
        },
      },
    ]);
  }

  async function handleSetMeeting() {
    setMeetingSaving(true);
    try {
      await api.patch(`/mobile/projects/${id}/meeting`, {
        next_meeting_at: meetingAt.trim() || null,
        next_meeting_notes: meetingNotes.trim() || null,
      });
      setMeetingModal(false);
      load();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Failed to update meeting.');
    } finally {
      setMeetingSaving(false);
    }
  }

  function openMeetingModal() {
    if (project?.next_meeting_at) {
      setMeetingAt(String(project.next_meeting_at).replace('Z', '').replace(' ', 'T').slice(0, 16));
    } else {
      setMeetingAt('');
    }
    setMeetingNotes(project?.next_meeting_notes ?? '');
    setMeetingModal(true);
  }

  async function openContactModal() {
    setDealerSearch('');
    try {
      const res = await api.get('/mobile/dealers');
      setDealers(res.data ?? []);
    } catch { /* ignore */ }
    setContactModal(true);
  }

  async function handleAssignContact(dealerId: number | null) {
    setContactSaving(true);
    try {
      await api.patch(`/mobile/projects/${id}/contact`, { dealer_id: dealerId });
      setContactModal(false);
      load();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Failed to update contact.');
    } finally {
      setContactSaving(false);
    }
  }

  async function handleWorkStatus(workId: number, newStatus: string) {
    setUpdatingWork(workId);
    try {
      await api.patch(`/mobile/works/${workId}`, { status: newStatus });
      setProject((prev) => prev ? {
        ...prev,
        works: prev.works.map((w) => w.id === workId ? { ...w, status: newStatus } : w),
      } : prev);
    } catch { Alert.alert('Error', 'Failed to update status.'); }
    finally { setUpdatingWork(null); }
  }

  if (loading) return <View style={styles.centered}><ActivityIndicator size="large" color={Colors.accent} /></View>;
  if (!project) return <View style={styles.centered}><Text style={{ color: Colors.textMuted }}>Project not found.</Text></View>;

  const meetingDays = project.next_meeting_at
    ? Math.round((new Date(project.next_meeting_at).getTime() - Date.now()) / 86400000)
    : null;

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScreenHeader
        title={project.project_number}
        subtitle={project.name}
        right={
          <TouchableOpacity
            onPress={() => router.push({ pathname: '/(tabs)/projects/form', params: { id: String(project.id) } })}
            style={styles.editBtn}
          >
            <Ionicons name="create-outline" size={20} color={Colors.primary} />
          </TouchableOpacity>
        }
      />
      <ScrollView
          contentContainerStyle={styles.content}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} tintColor={Colors.accent} />}
          showsVerticalScrollIndicator={false}
        >
          {/* Project header */}
          <View style={styles.heroCard}>
            <View style={styles.heroTop}>
              <Text style={styles.heroTitle} numberOfLines={2}>{project.name}</Text>
              <Badge status={project.status} />
            </View>
            <Text style={styles.heroSub}>{project.project_number}{project.type_of_work ? ` · ${project.type_of_work}` : ''}</Text>
          </View>

          {/* Next Meeting */}
          <Card style={styles.meetingCard}>
            <View style={styles.meetingBannerRow}>
              <Ionicons name="calendar" size={20} color={Colors.accent} />
              <View style={{ flex: 1, marginLeft: 10 }}>
                {project.next_meeting_at ? (
                  <>
                    <Text style={styles.meetingBannerLabel}>Next Meeting / Site Visit</Text>
                    <Text style={styles.meetingBannerDate}>{fmtDT(project.next_meeting_at)}</Text>
                    {project.next_meeting_notes && <Text style={styles.meetingBannerNotes}>{project.next_meeting_notes}</Text>}
                  </>
                ) : (
                  <Text style={styles.meetingBannerLabel}>No meeting scheduled</Text>
                )}
              </View>
              {meetingDays !== null && (
                <View style={[styles.daysBadge, meetingDays === 0 ? { backgroundColor: Colors.warningBg } : meetingDays < 0 ? { backgroundColor: Colors.dangerBg } : { backgroundColor: Colors.infoBg }]}>
                  <Text style={[styles.daysBadgeText, { color: meetingDays < 0 ? Colors.danger : meetingDays === 0 ? Colors.warning : Colors.info }]}>
                    {meetingDays === 0 ? 'Today' : meetingDays < 0 ? `${Math.abs(meetingDays)}d ago` : `${meetingDays}d`}
                  </Text>
                </View>
              )}
            </View>
            <TouchableOpacity style={styles.meetingSetBtn} onPress={openMeetingModal}>
              <Ionicons name="pencil-outline" size={14} color={Colors.accent} />
              <Text style={styles.meetingSetBtnText}>{project.next_meeting_at ? 'Update Meeting' : 'Schedule Meeting'}</Text>
            </TouchableOpacity>
          </Card>

          {/* Details */}
          <Card title="Project Details">
            <Row label="Client" value={project.client.name} />
            <Row label="Company" value={project.client.company} />
            <Row label="Contact" value={project.client.contact_person} />
            <Row label="Phone" value={project.client.phone} phone />
            <Row label="Email" value={project.client.email} email />
            <Row label="Start Date" value={fmt(project.start_date)} />
            <Row label="Expected End" value={fmt(project.expected_end_date)} />
            {project.budget && <Row label="Budget" value={`₹${Number(project.budget).toLocaleString('en-IN')}`} />}
            <Row label="Dealer" value={project.dealer?.name ?? null} />
            {project.description && <Row label="Description" value={project.description} />}
            {project.notes && <Row label="Notes" value={project.notes} />}
          </Card>

          {/* Locations */}
          {project.locations.length > 0 && (
            <Card title={`Locations (${project.locations.length})`}>
              {project.locations.map((loc, i) => (
                <View key={loc.id} style={[styles.locRow, i > 0 && styles.locBorder]}>
                  <Text style={styles.locName}>{loc.name}</Text>
                  <Text style={styles.locAddr}>{[loc.city, loc.state].filter(Boolean).join(', ')}</Text>
                  {loc.contact_person && <Text style={styles.locContact}>{loc.contact_person}{loc.contact_phone ? ` · ${loc.contact_phone}` : ''}</Text>}
                </View>
              ))}
            </Card>
          )}

          {/* Contacts */}
          <Card
            title="Contact"
            titleRight={
              <TouchableOpacity onPress={openContactModal}>
                <Ionicons name={project.dealer ? 'pencil-outline' : 'add-circle-outline'} size={22} color={Colors.accent} />
              </TouchableOpacity>
            }
          >
            {project.dealer ? (
              <View style={styles.contactRow}>
                <View style={styles.contactAvatar}>
                  <Text style={styles.contactInitials}>
                    {project.dealer.name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')}
                  </Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.contactName}>{project.dealer.name}</Text>
                  {project.dealer.city && <Text style={styles.contactMeta}>{project.dealer.city}</Text>}
                  {project.dealer.phone && (
                    <TouchableOpacity onPress={() => Linking.openURL(`tel:${project.dealer!.phone}`)}>
                      <Text style={[styles.contactMeta, { color: Colors.info }]}>{project.dealer.phone}</Text>
                    </TouchableOpacity>
                  )}
                </View>
                <TouchableOpacity onPress={() => handleAssignContact(null)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <Ionicons name="close-circle-outline" size={20} color={Colors.danger} />
                </TouchableOpacity>
              </View>
            ) : (
              <TouchableOpacity style={styles.addContactBtn} onPress={openContactModal}>
                <Ionicons name="person-add-outline" size={18} color={Colors.accent} />
                <Text style={styles.addContactText}>Assign a contact</Text>
              </TouchableOpacity>
            )}
          </Card>

          {/* Works */}
          <Card
            title={`Works (${project.works.length})`}
            titleRight={
              <TouchableOpacity onPress={() => router.push({ pathname: '/(tabs)/works/form', params: { projectId: String(project.id) } })}>
                <Ionicons name="add-circle-outline" size={22} color={Colors.accent} />
              </TouchableOpacity>
            }
          >
            {project.works.length === 0 ? (
              <Text style={styles.empty}>No works yet. Tap + to add.</Text>
            ) : (
              project.works.map((w, i) => (
                <View key={w.id} style={[styles.workRow, i > 0 && styles.locBorder]}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.workTitle} numberOfLines={1}>{w.title}</Text>
                    {w.due_date && <Text style={styles.workDue}>Due {fmt(w.due_date)}</Text>}
                    {/* Status chips */}
                    <View style={styles.statusChips}>
                      {WORK_STATUSES.map((opt) => (
                        <TouchableOpacity
                          key={opt.value}
                          style={[styles.statusChip, w.status === opt.value && styles.statusChipActive]}
                          onPress={() => handleWorkStatus(w.id, opt.value)}
                          disabled={updatingWork === w.id}
                        >
                          {updatingWork === w.id && w.status !== opt.value ? null : (
                            <Text style={[styles.statusChipText, w.status === opt.value && styles.statusChipTextActive]}>
                              {opt.label}
                            </Text>
                          )}
                        </TouchableOpacity>
                      ))}
                    </View>
                  </View>
                  <Badge status={w.priority} />
                </View>
              ))
            )}
          </Card>

          {/* Meeting History */}
          <Card
            title={`Meetings (${project.meetings.length + (project.next_meeting_at ? 1 : 0)})`}
            titleRight={
              <View style={{ flexDirection: 'row', gap: 10 }}>
                <TouchableOpacity onPress={openMeetingModal}>
                  <Ionicons name="calendar-outline" size={22} color={Colors.accent} />
                </TouchableOpacity>
                <TouchableOpacity onPress={() => { setLogDate(''); setLogPurpose(''); setLogModal(true); }}>
                  <Ionicons name="add-circle-outline" size={22} color={Colors.accent} />
                </TouchableOpacity>
              </View>
            }
          >
            {/* Upcoming meeting row */}
            {project.next_meeting_at && (
              <View style={styles.upcomingRow}>
                <View style={[styles.historyDateBox, { borderColor: Colors.accent + '40', backgroundColor: Colors.accent + '10' }]}>
                  <Text style={[styles.historyDay, { color: Colors.accent }]}>
                    {new Date(String(project.next_meeting_at).replace('Z','').replace(' ','T')).getDate()}
                  </Text>
                  <Text style={[styles.historyMonth, { color: Colors.accent }]}>
                    {new Date(String(project.next_meeting_at).replace('Z','').replace(' ','T')).toLocaleDateString('en-IN', { month: 'short', year: '2-digit' })}
                  </Text>
                </View>
                <View style={{ flex: 1 }}>
                  <View style={styles.upcomingBadgeWrap}>
                    <View style={styles.upcomingBadge}><Text style={styles.upcomingBadgeText}>UPCOMING</Text></View>
                  </View>
                  <Text style={styles.historyTime}>
                    {new Date(String(project.next_meeting_at).replace('Z','').replace(' ','T')).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })}
                  </Text>
                  {project.next_meeting_notes ? <Text style={[styles.historyPurpose, { marginTop: 1 }]} numberOfLines={1}>{project.next_meeting_notes}</Text> : null}
                </View>
                <TouchableOpacity
                  style={styles.logBtn}
                  onPress={() => {
                    setLogDate(String(project.next_meeting_at).replace('Z', '').replace(' ', 'T').slice(0, 16));
                    setLogPurpose(project.next_meeting_notes ?? '');
                    setLogModal(true);
                  }}
                >
                  <Text style={styles.logBtnText}>Log</Text>
                </TouchableOpacity>
              </View>
            )}

            {project.meetings.length === 0 && !project.next_meeting_at ? (
              <Text style={styles.empty}>No meetings yet. Tap 📅 to schedule or + to log.</Text>
            ) : (
              project.meetings.map((m, i) => (
                <View key={m.id} style={[styles.historyRow, (i > 0 || project.next_meeting_at) && styles.locBorder]}>
                  <View style={styles.historyDateBox}>
                    <Text style={styles.historyDay}>
                      {new Date(String(m.meeting_at).replace('Z','').replace(' ','T')).getDate()}
                    </Text>
                    <Text style={styles.historyMonth}>
                      {new Date(String(m.meeting_at).replace('Z','').replace(' ','T')).toLocaleDateString('en-IN', { month: 'short', year: '2-digit' })}
                    </Text>
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.historyPurpose}>{m.purpose}</Text>
                    <Text style={styles.historyTime}>
                      {new Date(String(m.meeting_at).replace('Z','').replace(' ','T')).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true })}
                      {m.created_by ? ` · ${m.created_by}` : ''}
                    </Text>
                  </View>
                  <TouchableOpacity onPress={() => handleDeleteMeeting(m.id)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                    <Ionicons name="trash-outline" size={16} color={Colors.danger} />
                  </TouchableOpacity>
                </View>
              ))
            )}
          </Card>
          {/* Activity */}
          <Card title="Activity">
            {activityLoading ? (
              <ActivityIndicator size="small" color={Colors.accent} style={{ paddingVertical: 12 }} />
            ) : activity.length === 0 ? (
              <Text style={styles.empty}>No activity recorded yet.</Text>
            ) : (
              activity.map((a, i) => (
                <View key={a.id} style={[styles.activityRow, i > 0 && styles.locBorder]}>
                  <View style={[styles.activityDot, { backgroundColor: actionColor(a.action) }]} />
                  <View style={{ flex: 1 }}>
                    <Text style={styles.activityDesc}>{a.description}</Text>
                    <Text style={styles.activityMeta}>
                      {a.user ? `${a.user} · ` : ''}{new Date(a.created_at).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}
                    </Text>
                  </View>
                </View>
              ))
            )}
          </Card>
        </ScrollView>

      {/* Log Meeting Modal */}
      <Modal visible={logModal} animationType="slide" presentationStyle="pageSheet">
        <RNSafeAreaView style={styles.modal}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Log Meeting</Text>
              <TouchableOpacity onPress={() => setLogModal(false)}>
                <Ionicons name="close" size={22} color={Colors.text} />
              </TouchableOpacity>
            </View>
            <View style={styles.modalBody}>
              <Text style={styles.modalLabel}>Date & Time</Text>
              <InlineDateTimeField value={logDate} onChange={setLogDate} />
              <Text style={styles.modalLabel}>Purpose</Text>
              <TextInput
                style={[styles.modalInput, styles.modalInputMulti]}
                value={logPurpose}
                onChangeText={setLogPurpose}
                placeholder="What was discussed / purpose of meeting…"
                placeholderTextColor={Colors.textMuted}
                multiline
                numberOfLines={3}
                maxLength={500}
              />
              <TouchableOpacity style={styles.modalBtn} onPress={handleLogMeeting} disabled={logSaving}>
                {logSaving ? <ActivityIndicator color={Colors.primary} /> : <Text style={styles.modalBtnText}>Log Meeting</Text>}
              </TouchableOpacity>
            </View>
          </KeyboardAvoidingView>
        </RNSafeAreaView>
      </Modal>

      {/* Set Next Meeting Modal */}
      <Modal visible={meetingModal} animationType="slide" presentationStyle="pageSheet">
        <RNSafeAreaView style={styles.modal}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Schedule Meeting</Text>
              <TouchableOpacity onPress={() => setMeetingModal(false)}>
                <Ionicons name="close" size={22} color={Colors.text} />
              </TouchableOpacity>
            </View>
            <View style={styles.modalBody}>
              <Text style={styles.modalLabel}>Date & Time</Text>
              <InlineDateTimeField value={meetingAt} onChange={setMeetingAt} />
              <Text style={styles.modalLabel}>Notes</Text>
              <TextInput
                style={styles.modalInput}
                value={meetingNotes}
                onChangeText={setMeetingNotes}
                placeholder="e.g. Site inspection with client"
                placeholderTextColor={Colors.textMuted}
                maxLength={250}
              />
              <TouchableOpacity style={styles.modalBtn} onPress={handleSetMeeting} disabled={meetingSaving}>
                {meetingSaving ? <ActivityIndicator color={Colors.primary} /> : <Text style={styles.modalBtnText}>Save Schedule</Text>}
              </TouchableOpacity>
              {project.next_meeting_at && (
                <TouchableOpacity
                  style={[styles.modalBtn, { backgroundColor: Colors.dangerBg, marginTop: 8 }]}
                  onPress={() => { setMeetingAt(''); setMeetingNotes(''); handleSetMeeting(); }}
                >
                  <Text style={[styles.modalBtnText, { color: Colors.danger }]}>Clear Schedule</Text>
                </TouchableOpacity>
              )}
            </View>
          </KeyboardAvoidingView>
        </RNSafeAreaView>
      </Modal>
      {/* Assign Contact Modal */}
      <Modal visible={contactModal} animationType="slide" presentationStyle="pageSheet">
        <RNSafeAreaView style={styles.modal}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Assign Contact</Text>
            <TouchableOpacity onPress={() => setContactModal(false)}>
              <Ionicons name="close" size={22} color={Colors.text} />
            </TouchableOpacity>
          </View>
          <View style={{ paddingHorizontal: 16, paddingTop: 12, paddingBottom: 8 }}>
            <View style={styles.searchWrap}>
              <Ionicons name="search-outline" size={16} color={Colors.textMuted} style={{ marginRight: 8 }} />
              <TextInput
                style={styles.searchInput}
                value={dealerSearch}
                onChangeText={setDealerSearch}
                placeholder="Search contacts…"
                placeholderTextColor={Colors.textMuted}
              />
            </View>
          </View>
          <ScrollView keyboardShouldPersistTaps="handled">
            {dealers
              .filter((d) => !dealerSearch || d.name.toLowerCase().includes(dealerSearch.toLowerCase()))
              .map((d) => (
                <TouchableOpacity
                  key={d.id}
                  style={[styles.dealerPickRow, project?.dealer?.id === d.id && styles.dealerPickRowActive]}
                  onPress={() => handleAssignContact(d.id)}
                  disabled={contactSaving}
                >
                  <View style={styles.dealerPickAvatar}>
                    <Text style={styles.dealerPickInitials}>
                      {d.name.split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')}
                    </Text>
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.dealerPickName}>{d.name}</Text>
                    {d.city && <Text style={styles.dealerPickMeta}>{d.city}</Text>}
                  </View>
                  {project?.dealer?.id === d.id && <Ionicons name="checkmark-circle" size={20} color={Colors.accent} />}
                </TouchableOpacity>
              ))}
            {contactSaving && <ActivityIndicator style={{ paddingVertical: 16 }} color={Colors.accent} />}
          </ScrollView>
        </RNSafeAreaView>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16, paddingBottom: 40 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  editBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: Colors.accent,
    alignItems: 'center', justifyContent: 'center',
  },
  heroCard: { backgroundColor: Colors.primary, borderRadius: 14, padding: 16, marginBottom: 12 },
  heroTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 6 },
  heroTitle: { fontSize: 18, fontWeight: '800', color: Colors.white, flex: 1, marginRight: 8 },
  heroSub: { fontSize: 12, color: 'rgba(255,255,255,0.65)' },
  meetingCard: { borderLeftWidth: 3, borderLeftColor: Colors.accent },
  meetingBannerRow: { flexDirection: 'row', alignItems: 'flex-start' },
  meetingBannerLabel: { fontSize: 11, fontWeight: '700', color: Colors.textSecondary, textTransform: 'uppercase', letterSpacing: 0.5 },
  meetingBannerDate: { fontSize: 15, fontWeight: '700', color: Colors.text, marginTop: 2 },
  meetingBannerNotes: { fontSize: 13, color: Colors.textSecondary, marginTop: 2 },
  daysBadge: { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, alignItems: 'center', justifyContent: 'center' },
  daysBadgeText: { fontSize: 12, fontWeight: '800' },
  meetingSetBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 10, alignSelf: 'flex-start' },
  meetingSetBtnText: { fontSize: 13, fontWeight: '600', color: Colors.accent },
  locRow: { paddingVertical: 10 },
  locBorder: { borderTopWidth: 1, borderTopColor: Colors.border },
  locName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  locAddr: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  locContact: { fontSize: 12, color: Colors.textMuted, marginTop: 2 },
  workRow: { flexDirection: 'row', alignItems: 'flex-start', paddingVertical: 10 },
  workTitle: { fontSize: 14, fontWeight: '600', color: Colors.text },
  workDue: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  statusChips: { flexDirection: 'row', flexWrap: 'wrap', gap: 4, marginTop: 8 },
  statusChip: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 12, backgroundColor: Colors.border },
  statusChipActive: { backgroundColor: Colors.primary },
  statusChipText: { fontSize: 10, fontWeight: '600', color: Colors.textSecondary },
  statusChipTextActive: { color: Colors.white },
  historyRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10 },
  historyDateBox: { alignItems: 'center', backgroundColor: Colors.primary + '12', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, marginRight: 12, minWidth: 50 },
  historyDay: { fontSize: 18, fontWeight: '800', color: Colors.primary },
  historyMonth: { fontSize: 10, fontWeight: '600', color: Colors.textSecondary },
  historyPurpose: { fontSize: 14, fontWeight: '700', color: Colors.text },
  historyTime: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  upcomingRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 10 },
  upcomingBadgeWrap: { flexDirection: 'row', marginBottom: 2 },
  upcomingBadge: { backgroundColor: Colors.accent + '20', borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2 },
  upcomingBadgeText: { fontSize: 9, fontWeight: '800', color: Colors.accent, letterSpacing: 0.5 },
  logBtn: { backgroundColor: Colors.accent + '18', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5, marginLeft: 8 },
  logBtnText: { fontSize: 12, fontWeight: '700', color: Colors.accent },
  empty: { color: Colors.textMuted, fontSize: 13, textAlign: 'center', paddingVertical: 12 },
  modal: { flex: 1, backgroundColor: Colors.background },
  modalHeader: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border, backgroundColor: Colors.white,
  },
  modalTitle: { fontSize: 17, fontWeight: '700', color: Colors.text },
  modalBody: { padding: 16 },
  modalLabel: { fontSize: 13, fontWeight: '600', color: Colors.text, marginBottom: 6, marginTop: 8 },
  modalInput: {
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.white, paddingHorizontal: 12, height: 48, fontSize: 15, color: Colors.text,
  },
  modalInputMulti: { height: 100, paddingTop: 12, textAlignVertical: 'top' },
  modalBtn: {
    backgroundColor: Colors.accent, borderRadius: 12, height: 50,
    alignItems: 'center', justifyContent: 'center', marginTop: 16,
  },
  modalBtnText: { fontSize: 16, fontWeight: '800', color: Colors.primary },
  dtBtn: {
    flexDirection: 'row', alignItems: 'center',
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.white, paddingHorizontal: 12, height: 48,
  },
  dtBtnText: { fontSize: 15, color: Colors.text },
  iosInline: {
    borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10,
    backgroundColor: Colors.white, marginTop: 4, overflow: 'hidden',
  },
  iosInlineRow: {
    flexDirection: 'row', justifyContent: 'space-between',
    paddingHorizontal: 16, paddingVertical: 10,
    borderTopWidth: 1, borderTopColor: Colors.border,
  },
  iosInlineBtn: { padding: 4 },
  iosInlineCancel: { fontSize: 15, color: Colors.textSecondary },
  iosInlineDone: { fontSize: 15, fontWeight: '700', color: Colors.accent },
  // Contacts
  contactRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 4 },
  contactAvatar: {
    width: 44, height: 44, borderRadius: 22,
    backgroundColor: Colors.info + '22', alignItems: 'center', justifyContent: 'center', marginRight: 12,
  },
  contactInitials: { fontSize: 15, fontWeight: '800', color: Colors.info },
  contactName: { fontSize: 14, fontWeight: '700', color: Colors.text },
  contactMeta: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
  addContactBtn: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 8 },
  addContactText: { fontSize: 14, color: Colors.accent, fontWeight: '600' },
  // Activity
  activityRow: { flexDirection: 'row', alignItems: 'flex-start', paddingVertical: 10, gap: 10 },
  activityDot: { width: 8, height: 8, borderRadius: 4, marginTop: 5 },
  activityDesc: { fontSize: 13, color: Colors.text, lineHeight: 18 },
  activityMeta: { fontSize: 11, color: Colors.textMuted, marginTop: 3 },
  // Contact picker
  searchWrap: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.background, borderRadius: 10,
    borderWidth: 1.5, borderColor: Colors.border, paddingHorizontal: 12, height: 42,
  },
  searchInput: { flex: 1, fontSize: 14, color: Colors.text },
  dealerPickRow: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 16, paddingVertical: 14,
    borderBottomWidth: 1, borderBottomColor: Colors.border,
  },
  dealerPickRowActive: { backgroundColor: Colors.accent + '12' },
  dealerPickAvatar: {
    width: 40, height: 40, borderRadius: 20,
    backgroundColor: Colors.info + '22', alignItems: 'center', justifyContent: 'center', marginRight: 12,
  },
  dealerPickInitials: { fontSize: 14, fontWeight: '800', color: Colors.info },
  dealerPickName: { fontSize: 14, fontWeight: '600', color: Colors.text },
  dealerPickMeta: { fontSize: 12, color: Colors.textSecondary, marginTop: 2 },
});

const dl = StyleSheet.create({
  row: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: Colors.border },
  label: { fontSize: 13, color: Colors.textSecondary, flex: 1 },
  value: { fontSize: 13, color: Colors.text, fontWeight: '600', flex: 1.5, textAlign: 'right' },
});
